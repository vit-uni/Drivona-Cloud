<?php

namespace app\controller;

use support\Request;
use support\think\Db;
use support\Log;

/**
 * 非导航模式 —— 获取前方摄像头 / 事件列表
 *
 * 背景：
 *   导航模式下 App 调用 IndexController::devices 并传入真实道路折线（route），
 *   后端用 Location::filterCamerasBySide 沿道路折线做路线感知过滤。
 *   非导航模式下没有真实路线，App 生成的是一组「扇形点」（5 条射线 × 10 距离环，
 *   ±15°、约 500m，见 runtime/doc/blizter/NonNavReminderController.kt），并非真实道路折线。
 *
 * 本控制器采用「行驶方向 + 前方扇区 + 横向走廊」对摄像头 / 事件做匹配：
 *   1. 方向匹配：dirtype/direction 与行驶方向兼容
 *   2. 前方扇区：摄像头方位角与行驶方向夹角 <= FRONT_ANGLE
 *   3. 距离：<= MAX_DISTANCE
 *   4. 横向走廊：横向偏移 <= max(CORRIDOR_BASE, distance * CORRIDOR_SLOPE)（上限 CORRIDOR_MAX）
 *      —— 近距离近似导航的路线缓冲区，远距离随距离线性放宽以贴合道路缓弯
 *
 * 所需地理计算方法均从 app\model\Location 复制而来，原文件不做任何修改。
 *
 * 接口：GET/POST /nonNav/devices、/nonNav/events
 * 参数：lat、long、direction（行驶方向角）、route（可选，非导航扇形点，仅兼容接收不参与匹配）
 */
class NonNavController
{
    /**
     * 需要登录的方法（空数组 = 所有方法均需登录，与导航 devices 一致，请求头需带 token）
     */
    protected $noNeedLogin = [];

    // ======================= 可调参数（按实际效果调整） =======================

    /** 搜索半径（米）。非导航只取最近摄像头做 BLE 预警，10km 已足够，候选量大减、查询更快 */
    private const MAX_DISTANCE = 2000;

    /**
     * 前方扇区：摄像头方位角与行驶方向夹角上限（度）；90° 即前半平面。
     *
     * 注意：这是「前方 / 后方」的二分。后端拿不到车速，无法区分「真实掉头」与
     * 「低速/静止时 GPS bearing 180° 翻转」——后者会让一个本在前方的摄像头被判为后方而剔除，
     * 造成 show/hide 闪烁。该问题必须由 App 端对行驶方向角做稳定化（低速不采信 GPS bearing），
     * 后端保持此语义不变。
     */
    private const FRONT_ANGLE = 90;

    /** dirtype 方向匹配容差（度），与 Location::DIR_MATCH_ANGLE 一致 */
    private const DIR_MATCH_ANGLE = 30;

    /** 横向走廊最小宽度（米），近距离用于抑制相邻/并行道路 */
    private const CORRIDOR_BASE = 30;

    /**
     * 横向走廊随距离放宽的斜率（≈11.3° 半角）。
     *
     * 横向偏移 ≈ 距离 × sin(方向角误差)，若阈值与距离无关（如 Location 里的固定 20m），
     * 远距离摄像头会因正常的 GPS heading 漂移（约 8~25°）或道路缓弯而反复被剔除，
     * 表现为 App 端同一摄像头「查到→查不到→又查到」。远距离摄像头不触发告警
     * （告警区约 1000m 内），只需稳定进入列表供 App 跟踪，因此允许牺牲一点精度换召回。
     * 该值是可调参数，建议用真实路测日志回灌微调。
     */
    private const CORRIDOR_SLOPE = 0.20;

    /** 横向走廊宽度上限（米），避免远距离放得过宽 */
    private const CORRIDOR_MAX = 800;

    /**
     * devices 专用放宽横向走廊参数（仅 $relaxNearField=true 时生效，events 不受影响）。
     *
     * 背景：日志分析发现原 CORRIDOR_SLOPE=0.20（≈11.3° 半角）+ CORRIDOR_BASE=30 下存在两类闪烁：
     *
     *   Situation 1（间歇性无数据）：摄像头横向偏移（cross-track）在走廊边缘时，车辆沿同一方向
     *   行驶接近导致走廊线性收窄（distance × 0.20），cross-track 基本不变而走廊持续变窄，
     *   摄像头从「刚好通过」翻转为「刚好不通过」，连续多次返回空列表后又恢复——
     *   实测 cam-121674 在 2030m 处 corridor=406m（cross-track=393m，通过），车辆前进 90m
     *   到 1937m 处 corridor=387m（cross-track=393m，不通过），连续 5 次无数据。
     *
     *   Situation 2（首位摄像头 ID 跳变）：方向角仅变化 3~4° 时，cross-track 随之偏移数十米，
     *   加之走廊随距离收窄，摄像头在「通过/不通过」之间翻转，首位 ID 在不同摄像头间跳变——
     *   实测方向角 28.29°→24.93°（仅 3.36°），cam-121693 的 cross-track 从 101m 升至 150m，
     *   corridor 从 160m 降至 145m，从通过翻转为不通过，首位 ID 从 121693 跳变为 121685。
     *
     * 放宽到 SLOPE=0.30（≈17.5° 半角）+ BASE=100m 后：
     *   - 2000m 处走廊 600m（原 400m），cross-track ~400m 的摄像头稳定通过
     *   - 近距离 base 100m（原 30m），防止近场摄像头因微小横向偏移被剔除
     *   - 仍能有效抑制真正偏离行驶方向的相邻/并行道路摄像头（17.5° 半角仍在合理范围）
     */
    private const CORRIDOR_BASE_RELAXED = 100;
    private const CORRIDOR_SLOPE_RELAXED = 0.30;

    /** SQL 侧候选上限（已按距离排序，取最近的 N 条，防止密集区域拉取过多行） */
    private const SQL_SAFETY_LIMIT = 1000;

    /** 最终结果上限（按距离升序截断，略高于导航的 200） */
    private const QUERY_LIMIT = 300;

    /**
     * 附加坐标点（additionalcoordinate）开关：
     *   false —— 仅按主坐标匹配，additionalcoordinate 原样返回（与导航 devices 一致）
     *   true  —— 额外解析附加预警坐标点，命中则作为独立结果追加（带 is_additional=1）
     */
    private const USE_ADDITIONAL_COORDS = false;

    /** 地球半径（米） */
    private const EARTH_RADIUS = 6371000.0;

    /**
     * 接口提示语文案：中文原文 => [en, de]
     */
    private static $i18n = [
        '成功' => ['Success', 'Erfolg'],
        '经纬度参数无效' => ['Invalid latitude/longitude', 'Ungültige Breiten-/Längengrade'],
    ];

    /**
     * 根据请求头 language（de/en）返回对应语言文案，缺省中文
     */
    private static function t(Request $request, string $key): string
    {
        $lang = strtolower(trim(substr((string)$request->header('language', ''), 0, 2)));
        if ($lang === 'en' && isset(self::$i18n[$key][0])) {
            return self::$i18n[$key][0];
        }
        if ($lang === 'de' && isset(self::$i18n[$key][1])) {
            return self::$i18n[$key][1];
        }
        return $key;
    }

    /**
     * 获取非导航模式下的前方摄像头列表
     *
     * @param Request $request
     * @return \support\Response
     */
    public function devices(Request $request)
    {
        $start = microtime(true);

        // 1. 参数获取与类型转换（float 转换防 SQL 注入）
        $lat       = (float) $request->input('lat');
        $long      = (float) $request->input('long');
        $direction = (float) $request->input('direction', 0);
        // 非导航模式下 App 会传一组扇形点；它不是真实道路折线，这里仅兼容接收，不参与几何匹配
        $route     = $request->input('route');

        // 2. 参数合法性校验
        if ($lat < -90 || $lat > 90 || $long < -180 || $long > 180) {
            Log::info(sprintf('[NonNav][devices] invalid lat/long lat=%s long=%s', $lat, $long));
            return json(['code' => 0, 'msg' => self::t($request, '经纬度参数无效')]);
        }

        $maxDistance = self::MAX_DISTANCE;

        // 3. 边界框粗筛：按 MAX_DISTANCE × 1.1 计算（略放安全边界），复用 idx_lng_lat 索引
        $boxDistance     = $maxDistance * 1.1;
        $metersPerDegLat = 111320.0;
        $metersPerDegLng = 111320.0 * cos(deg2rad($lat));
        $latRadius       = $boxDistance / $metersPerDegLat;
        $lngRadius       = $boxDistance / max($metersPerDegLng, 0.001);

        // 4. 查询：边界框粗筛 + 距离计算/排序都在 SQL 完成，取最近 SQL_SAFETY_LIMIT 条。
        //    ORDER BY distance 保证最近摄像头一定在候选集内（置顶），不会因 LIMIT 截断而漏掉最近的；
        //    10km 框行数少，ST_Distance_Sphere + 排序在服务端仅毫秒级。
        $queryStart = microtime(true);
        $list = Db::name('device')
            ->field("id, type, speed, longitude, latitude, direction, dirtype, camera_id, city, street, additionalcoordinate,
                     ST_Distance_Sphere(point({$long}, {$lat}), point(longitude, latitude)) AS distance")
            ->where("longitude BETWEEN ? AND ?", [$long - $lngRadius, $long + $lngRadius])
            ->where("latitude BETWEEN ? AND ?", [$lat - $latRadius, $lat + $latRadius])
            ->order('distance ASC')
            ->limit(self::SQL_SAFETY_LIMIT)
            ->select();
        $queryMs = round((microtime(true) - $queryStart) * 1000, 2);

        // 5. 方向 + 扇区 + 横向走廊过滤
        $vehicle     = ['lat' => $lat, 'lng' => $long, 'bearing' => $direction];
        $filterStart = microtime(true);
        $data        = self::filterAhead($vehicle, $list, $maxDistance);
        $filterMs    = round((microtime(true) - $filterStart) * 1000, 2);

        $totalMs = round((microtime(true) - $start) * 1000, 2);
        // 记录首位结果（id:distance），用于诊断 App show/hide 闪烁时“返回的是否同一摄像头”
        $top = $data[0] ?? null;
        Log::info(sprintf(
            '[NonNav][devices] lat=%.6f long=%.6f direction=%.2f maxDistance=%d route=%s useAdditional=%s candidates=%d result=%d topId=%s topDist=%s queryMs=%.2f filterMs=%.2f totalMs=%.2f',
            $lat,
            $long,
            $direction,
            $maxDistance,
            $route ? 'yes' : 'no',
            self::USE_ADDITIONAL_COORDS ? 'true' : 'false',
            count($list),
            count($data),
            $top['id'] ?? '-',
            isset($top['distance']) ? round($top['distance'], 1) : '-',
            $queryMs,
            $filterMs,
            $totalMs
        ));

        return json(['code' => 1, 'msg' => self::t($request, '成功'), 'data' => $data]);
    }

    /**
     * 获取非导航模式下的前方事件列表
     *
     * 查询与过滤逻辑与 devices 完全一致（同一套「方向 + 前方扇区 + 横向走廊」）：
     *   - 查询：边界框粗筛 + ST_Distance_Sphere 距离计算/排序 + LIMIT，距离上限在 PHP 侧统一裁剪；
     *   - 过滤：调用同一个 filterAhead，方向始终按 dirtype/direction 校验（事件方向 vs 行驶方向），
     *     并使用与 devices 相同的放宽横向走廊，保证同一位置的摄像头与事件过滤口径一致。
     * 唯一差异是数据表不同（device_event vs device），directiontype 别名映射为 dirtype。
     *
     * 与导航 events 的差异仅在于：非导航没有真实道路折线，route 只是一组
     * 扇形点，不能用于路线缓冲区匹配，因此这里仅兼容接收、不参与几何计算。
     *
     * 接口：GET/POST /nonNav/events
     * 参数：lat、long、direction（行驶方向角）、route（可选，非导航扇形点，仅兼容接收不参与匹配）
     *
     * @param Request $request
     * @return \support\Response
     */
    public function events(Request $request)
    {
        $start = microtime(true);

        // 1. 参数获取与类型转换（float 转换防 SQL 注入）
        $lat       = (float) $request->input('lat');
        $long      = (float) $request->input('long');
        $direction = (float) $request->input('direction', 0);
        // 非导航模式下 App 传的是扇形点，不是真实道路折线，仅兼容接收，不参与几何匹配
        $route     = $request->input('route');

        // 2. 参数合法性校验
        if ($lat < -90 || $lat > 90 || $long < -180 || $long > 180) {
            Log::info(sprintf('[NonNav][events] invalid lat/long lat=%s long=%s', $lat, $long));
            return json(['code' => 0, 'msg' => self::t($request, '经纬度参数无效')]);
        }

        // 与 devices 共用同一个搜索半径，保证摄像头与事件覆盖范围一致
        $maxDistance = self::MAX_DISTANCE;

        // 3. 边界框粗筛：按 MAX_DISTANCE × 1.1 计算（略放安全边界），复用 idx_lng_lat 索引
        $boxDistance     = $maxDistance * 1.1;
        $metersPerDegLat = 111320.0;
        $metersPerDegLng = 111320.0 * cos(deg2rad($lat));
        $latRadius       = $boxDistance / $metersPerDegLat;
        $lngRadius       = $boxDistance / max($metersPerDegLng, 0.001);

        // 4. 查询：与 devices 结构一致 —— 边界框粗筛 + 距离计算/排序都在 SQL 完成，
        //    取最近 SQL_SAFETY_LIMIT 条；距离上限由 PHP 侧 filterAhead 统一裁剪。
        //    directiontype 与摄像头 dirtype 语义一致（0=单向，1=双向，2=全向），统一别名，
        //    以便直接复用与 devices 相同的 filterAhead 过滤逻辑。
        $queryStart = microtime(true);
        $list = Db::name('device_event')
            ->field("id, type, speed, longitude, latitude, direction, directiontype as dirtype, city, street,
                     ST_Distance_Sphere(point({$long}, {$lat}), point(longitude, latitude)) AS distance")
            ->where("longitude BETWEEN ? AND ?", [$long - $lngRadius, $long + $lngRadius])
            ->where("latitude BETWEEN ? AND ?", [$lat - $latRadius, $lat + $latRadius])
            ->order('distance ASC')
            ->limit(self::SQL_SAFETY_LIMIT)
            ->select();
        $queryMs = round((microtime(true) - $queryStart) * 1000, 2);

        // 5. 方向 + 扇区 + 横向走廊过滤（与 devices 完全一致，使用相同的放宽走廊参数）。
        //    方向匹配始终校验：事件方向(direction/directiontype)需与车辆行驶方向(direction)兼容，
        //    避免把对面车道/逆向的事件查出来。
        $vehicle     = ['lat' => $lat, 'lng' => $long, 'bearing' => $direction];
        $filterStart = microtime(true);
        $data        = self::filterAhead($vehicle, $list, $maxDistance);
        $filterMs    = round((microtime(true) - $filterStart) * 1000, 2);

        $totalMs = round((microtime(true) - $start) * 1000, 2);
        // 记录首位结果（id/type/distance），用于诊断导航与非导航结果差异及 App 告警切换
        $top = $data[0] ?? null;
        Log::info(sprintf(
            '[NonNav][events] lat=%.6f long=%.6f direction=%.2f maxDistance=%d route=%s candidates=%d result=%d topId=%s topType=%s topDist=%s queryMs=%.2f filterMs=%.2f totalMs=%.2f',
            $lat,
            $long,
            $direction,
            $maxDistance,
            $route ? 'yes' : 'no',
            count($list),
            count($data),
            $top['id'] ?? '-',
            $top['type'] ?? '-',
            isset($top['distance']) ? round($top['distance'], 1) : '-',
            $queryMs,
            $filterMs,
            $totalMs
        ));

        return json(['code' => 1, 'msg' => self::t($request, '成功'), 'data' => $data]);
    }

    /**
     * 「方向 + 扇区 + 横向走廊」过滤
     *
     * @param array $vehicle  ['lat' => float, 'lng' => float, 'bearing' => float]
     * @param iterable $cameras 数据库候选集（think\Collection 或数组）
     * @param float $maxDistance 最大距离（米）
     * @param bool $relaxNearField 是否使用放宽的横向走廊（仅影响走廊宽度，方向匹配始终生效）：
     *        devices / events 均使用默认 true（走廊 Base 100m / Slope 0.30，防止临近道路抖动导致 show/hide 闪烁）；
     *        传 false 时回退到严格走廊（Base 30m / Slope 0.20），当前无调用方使用
     * @return array 匹配结果，按 distance 升序
     */
    private static function filterAhead(array $vehicle, iterable $cameras, float $maxDistance, bool $relaxNearField = true): array
    {
        $vLat     = (float) $vehicle['lat'];
        $vLng     = (float) $vehicle['lng'];
        $vBearing = self::normalizeBearing($vehicle['bearing']);

        $result = [];

        foreach ($cameras as $camera) {
            $cLat = (float) $camera['latitude'];
            $cLng = (float) $camera['longitude'];
            $dirtype = (int) ($camera['dirtype'] ?? 0);

            $distance = isset($camera['distance'])
                ? (float) $camera['distance']
                : self::haversineDistance($vLat, $vLng, $cLat, $cLng);

            // ---- 主坐标匹配 ----
            $matched = self::matchPoint(
                $vLat,
                $vLng,
                $vBearing,
                $camera,
                $cLat,
                $cLng,
                $camera['direction'] ?? null,
                $dirtype,
                $distance,
                $maxDistance,
                false,
                null,
                $relaxNearField
            );
            if ($matched !== null) {
                $result[] = $matched;
            }

            // ---- 附加预警坐标点匹配（开关控制）----
            if (self::USE_ADDITIONAL_COORDS && !empty($camera['additionalcoordinate'])) {
                $extraPoints = json_decode($camera['additionalcoordinate'], true);
                if (is_array($extraPoints)) {
                    foreach ($extraPoints as $point) {
                        if (!isset($point['latitude'], $point['longitude'])) {
                            continue;
                        }
                        $pLat = (float) $point['latitude'];
                        $pLng = (float) $point['longitude'];
                        if ($pLat == 0.0 && $pLng == 0.0) {
                            continue;
                        }
                        // 附加点方向为空时回退到父摄像头方向
                        $pDirection = $point['direction'] ?? null;
                        if ($pDirection === null || $pDirection === '') {
                            $pDirection = $camera['direction'] ?? null;
                        }
                        $pDistance = self::haversineDistance($vLat, $vLng, $pLat, $pLng);

                        $pMatched = self::matchPoint(
                            $vLat,
                            $vLng,
                            $vBearing,
                            $camera,
                            $pLat,
                            $pLng,
                            $pDirection,
                            $dirtype,
                            $pDistance,
                            $maxDistance,
                            true,
                            $point['id'] ?? null,
                            $relaxNearField
                        );
                        if ($pMatched !== null) {
                            $result[] = $pMatched;
                        }
                    }
                }
            }
        }

        // 按距离升序（导航接口同样按距离排序，App 取 this[0] 作为最近点）
        usort($result, static function ($a, $b) {
            $distA = isset($a['distance']) ? (int) $a['distance'] : PHP_INT_MAX;
            $distB = isset($b['distance']) ? (int) $b['distance'] : PHP_INT_MAX;
            return $distA <=> $distB;
        });

        // 只返回最近 QUERY_LIMIT 条，防止极端密集区域一次返回过多
        return array_slice($result, 0, self::QUERY_LIMIT);
    }

    /**
     * 单点匹配：方向 + 前方扇区 + 距离 + 横向走廊
     *
     * @param float      $vLat
     * @param float      $vLng
     * @param float      $vBearing     车辆行驶方向（已标准化）
     * @param array      $camera        摄像头原始行
     * @param float      $pLat          待匹配点纬度（主坐标或附加坐标）
     * @param float      $pLng          待匹配点经度
     * @param mixed      $pointDirection 待匹配点方向
     * @param int        $dirtype       监控方向类型
     * @param float      $distance      车辆到待匹配点的距离（米）
     * @param float      $maxDistance   最大距离（米）
     * @param bool       $isAdditional  是否为附加坐标点
     * @param mixed      $additionalId  附加点自身 id（用于让 App 区分去重）
     * @param bool       $relaxNearField 是否使用放宽的横向走廊（见 filterAhead）
     * @return array|null 匹配成功返回结果行，否则 null
     */
    private static function matchPoint(
        $vLat,
        $vLng,
        $vBearing,
        array $camera,
        $pLat,
        $pLng,
        $pointDirection,
        $dirtype,
        $distance,
        $maxDistance,
        $isAdditional = false,
        $additionalId = null,
        $relaxNearField = true
    ) {
        // 1. 方向匹配：监控方向需与行驶方向兼容（dirtype：0=当前车道单向，1=双向，2=全向）。
        //    与 events / IndexController::devices 一致，始终校验，避免把对面车道(dirtype=0 反向)
        //    摄像头误报；前方扇区与横向走廊在两种模式下始终生效。
        if (!self::matchesMonitoringDirection($vBearing, $pointDirection, $dirtype)) {
            return null;
        }

        // 2. 前方扇区：摄像头需位于车辆前方。
        //    此处依赖行驶方向角；若 App 传入了 180° 翻转的 bearing，会把前方摄像头误判为后方，
        //    这是后端无法在无车速信息下消除的（详见 FRONT_ANGLE 注释），需 App 端稳定化方向角。
        $geoBearing = self::getBearingFast($vLat, $vLng, $pLat, $pLng);
        if (self::getAngleDiff($vBearing, $geoBearing) > self::FRONT_ANGLE) {
            return null;
        }

        // 3. 距离限制
        if ($distance > $maxDistance) {
            return null;
        }

        // 4. 横向走廊：抑制相邻/并行道路，随距离放宽以贴合缓弯
        //    devices / events（$relaxNearField=true）均使用放宽走廊参数，防止走廊线性收窄导致
        //    摄像头反复出现/消失（Situation 1）及小方向角变化导致首位 ID 跳变（Situation 2）。
        //    $relaxNearField=false 时为严格走廊（Base 30m / Slope 0.20），当前无调用方使用。
        $crossTrack = self::getCrossTrackDistance($distance, $geoBearing, $vBearing);
        if ($relaxNearField) {
            $corridorBase  = self::CORRIDOR_BASE_RELAXED;
            $corridorSlope = self::CORRIDOR_SLOPE_RELAXED;
        } else {
            $corridorBase  = self::CORRIDOR_BASE;
            $corridorSlope = self::CORRIDOR_SLOPE;
        }
        $corridorCap = min(
            max($corridorBase, $distance * $corridorSlope),
            self::CORRIDOR_MAX
        );
        if ($crossTrack > $corridorCap) {
            return null;
        }

        // 5. 构造返回（字段与导航 devices 保持一致）
        $out = $camera;
        $out['latitude']         = $pLat;
        $out['longitude']        = $pLng;
        $out['direction']        = $pointDirection;
        $out['distance']         = (int) round($distance);
        $out['match_latitude']   = $pLat;
        $out['match_longitude']  = $pLng;

        if ($isAdditional) {
            $out['is_additional'] = 1;
            // 附加点若有独立 id，则用它区分，便于 App 去重；否则沿用父摄像头 id
            if ($additionalId !== null && $additionalId !== '') {
                $out['id'] = is_numeric($additionalId) ? (int) $additionalId : $additionalId;
            }
        }

        return $out;
    }

    // ======================= 以下为从 app\model\Location 复制的工具方法 =======================

    /**
     * 判断摄像头监控方向是否与车辆行驶方向匹配
     * dirtype：0=单向，1=双向，2=全向
     */
    private static function matchesMonitoringDirection($vehicleBearing, $cameraDirection, $dirtype): bool
    {
        $dirtype        = (int) $dirtype;
        $vehicleBearing = self::normalizeBearing($vehicleBearing);

        if ($dirtype === 2) {
            return true;
        }

        if ($cameraDirection === null || $cameraDirection === '') {
            return $dirtype === 2;
        }

        $cameraDirection = self::normalizeBearing($cameraDirection);

        if ($dirtype === 0) {
            return self::getAngleDiff($cameraDirection, $vehicleBearing) <= self::DIR_MATCH_ANGLE;
        }

        if ($dirtype === 1) {
            $reverse = self::normalizeBearing($cameraDirection + 180);
            return self::getAngleDiff($cameraDirection, $vehicleBearing) <= self::DIR_MATCH_ANGLE
                || self::getAngleDiff($reverse, $vehicleBearing) <= self::DIR_MATCH_ANGLE;
        }

        return false;
    }

    /**
     * 使用 Haversine 公式计算两点间的球面距离（米）
     */
    private static function haversineDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLng = deg2rad((float) $lng2 - (float) $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($dLng / 2) ** 2;
        return 2 * self::EARTH_RADIUS * asin(min(1.0, sqrt($a)));
    }

    /**
     * 计算点到航线的横向距离（米）
     */
    private static function getCrossTrackDistance($distance, $bearingToPoint, $vehicleBearing): float
    {
        $angleDiff   = deg2rad($bearingToPoint - $vehicleBearing);
        $distanceRad = $distance / self::EARTH_RADIUS;
        return abs(asin(sin($distanceRad) * sin($angleDiff)) * self::EARTH_RADIUS);
    }

    /**
     * 快速计算从起点到终点的方位角（度数）
     */
    private static function getBearingFast($lat1, $lng1, $lat2, $lng2): float
    {
        $lat1 = deg2rad((float) $lat1);
        $lng1 = deg2rad((float) $lng1);
        $lat2 = deg2rad((float) $lat2);
        $lng2 = deg2rad((float) $lng2);

        $y = sin($lng2 - $lng1) * cos($lat2);
        $x = cos($lat1) * sin($lat2)
            - sin($lat1) * cos($lat2) * cos($lng2 - $lng1);

        $bearing = atan2($y, $x);
        $bearing = rad2deg($bearing);
        return fmod(($bearing + 360), 360);
    }

    /**
     * 计算两个角度之间的最短差值（考虑 360° 循环）
     */
    private static function getAngleDiff($angle1, $angle2): float
    {
        $diff = fmod(abs((float) $angle1 - (float) $angle2), 360);
        return $diff > 180 ? 360 - $diff : $diff;
    }

    /**
     * 将任意角度标准化到 [0°, 360°] 范围内
     */
    private static function normalizeBearing($bearing): float
    {
        return fmod(fmod((float) $bearing, 360.0) + 360.0, 360.0);
    }
}
