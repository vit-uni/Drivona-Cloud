<?php
namespace app\model;



/**
 * SCDB 固定摄像头 GPS 轨迹匹配
 * 简化版：仅匹配主坐标，不处理附加预警坐标
 */
class Location
// 定义 Location 类，继承自 ThinkPHP 的 Model 基类
{
    const FRONT_ANGLE = 90;
    // 前方角度范围常量：车辆正前方 ±45° 共 90° 的扇形区域

    const DIR_MATCH_ANGLE = 30;
    // 方向匹配角度常量：摄像头监控方向与车辆行驶方向的允许偏差角度

    const MAX_CROSS_TRACK_DISTANCE = 20;
    // 最大横向距离常量：摄像头偏离车辆行驶轨迹的最大允许距离（米）

    const SPEED_LIMIT_LATERAL_BUFFER = 35;
    // 限速路段横向偏移容差（米）：仅用于「已进入路段」（state=1）。
    // 车辆已在路段上，横向偏移来自直线段近似曲线道路，需给足余量（1786277 入口点 lateral=18.8m）。

    const SPEED_LIMIT_LATERAL_MIN = 12;
    // 接近阶段（state=0）横向偏移下限（米）：近距离时的最小横向容忍度，剔除旁边道路/对向车道。

    const SPEED_LIMIT_APPROACH_ANGLE = 10;
    // 接近阶段（state=0）横向偏移角度容差（度）：atan2(|lateral|, |along|) 超过该角度
    // 即视为「从侧面接近」而剔除；角偏差本身与距离无关，故用角度而非固定米数。

    const EARTH_RADIUS = 6371000.0;
    // 地球半径常量：用于地理距离计算（米）

    public static function filterCamerasInFront($vehicle, $cameras, $maxDistance = null)
    // 方案一：基础匹配方法，根据车辆位置和方向过滤出车辆前方的摄像头
    // 参数：$vehicle 车辆信息, $cameras 摄像头列表, $maxDistance 最大距离限制
    {
        if (empty($cameras)) {
            // 如果摄像头列表为空，直接返回空数组
            return [];
            // 返回空数组
        }

        $vLat     = (float) $vehicle['lat'];
        // 提取车辆纬度并转换为浮点数
        $vLng     = (float) $vehicle['lng'];
        // 提取车辆经度并转换为浮点数
        $vBearing = self::normalizeBearing($vehicle['bearing']);
        // 提取车辆行驶方向并标准化到 [0°, 360°] 范围
        $vLatRad  = deg2rad($vLat);
        // 将车辆纬度转换为弧度（用于后续三角函数计算）
        $vLngRad  = deg2rad($vLng);
        // 将车辆经度转换为弧度（用于后续三角函数计算）

        $result = [];
        // 初始化结果数组
        foreach ($cameras as $camera) {
            // 遍历摄像头列表
            $matched = self::matchCamera($vLat, $vLng, $vLatRad, $vLngRad, $vBearing, $camera, $maxDistance);
            // 调用 matchCamera 方法匹配单个摄像头
            if ($matched !== null) {
                // 如果匹配成功
                $result[] = $matched;
                // 将匹配的摄像头加入结果数组
            }
        }

        usort($result, static function ($a, $b) {
            // 使用 usort 按距离从小到大排序
            $distA = isset($a['distance']) ? (int) round($a['distance']) : PHP_INT_MAX;
            // 获取摄像头 A 的距离，不存在则设为最大值
            $distB = isset($b['distance']) ? (int) round($b['distance']) : PHP_INT_MAX;
            // 获取摄像头 B 的距离，不存在则设为最大值
            return $distA <=> $distB;
            // 比较距离，返回排序结果
        });

        return $result;
        // 返回排序后的匹配摄像头列表
    }

    private static function matchCamera($vLat, $vLng, $vLatRad, $vLngRad, $vBearing, $camera, $maxDistance)
    // 基础匹配核心方法：匹配单个摄像头是否符合条件
    // 参数：车辆位置(经纬度/弧度)、行驶方向、摄像头信息、最大距离限制
    {
        $dirtype   = (int) ($camera['dirtype'] ?? 0);
        // 提取摄像头监控类型，默认值为 0（单向监控）
        $direction = $camera['direction'] ?? null;
        // 提取摄像头监控方向，可能为 null

        if (!self::matchesMonitoringDirection($vBearing, $direction, $dirtype)) {
            // 步骤1：检查摄像头监控方向是否与车辆行驶方向兼容
            return null;
            // 不匹配则返回 null
        }

        $pLat = (float) $camera['latitude'];
        // 提取摄像头纬度
        $pLng = (float) $camera['longitude'];
        // 提取摄像头经度
        $geoBearing = self::getBearingFast($vLat, $vLng, $pLat, $pLng);
        // 计算从车辆到摄像头的地理方位角

        if (self::getAngleDiff($vBearing, $geoBearing) > self::FRONT_ANGLE) {
            // 步骤2：检查摄像头是否在车辆正前方 90° 范围内
            return null;
            // 不在前方范围则返回 null
        }

        $distance = isset($camera['distance'])
            ? (float) $camera['distance']
            : self::haversineDistance($vLat, $vLng, $pLat, $pLng);
        // 步骤3：获取车辆到摄像头的距离，优先使用已有值，否则用 Haversine 公式计算

        if ($maxDistance !== null && $distance > $maxDistance) {
            // 步骤3：检查距离是否超过最大限制
            return null;
            // 超过距离限制则返回 null
        }

        $crossTrack = self::getCrossTrackDistance($distance, $geoBearing, $vBearing);
        // 步骤4：计算摄像头相对于车辆行驶轨迹的横向偏移距离
        if ($crossTrack > self::MAX_CROSS_TRACK_DISTANCE) {
            // 检查横向偏移是否超过 50 米
            return null;
            // 超过横向距离限制则返回 null
        }

        $camera['distance'] = (int) round($distance);
        // 设置匹配后的距离（四舍五入取整）
        $camera['match_latitude']  = $pLat;
        // 设置匹配纬度
        $camera['match_longitude'] = $pLng;
        // 设置匹配经度

        return $camera;
        // 返回匹配成功的摄像头信息
    }

    public static function matchesMonitoringDirection($vehicleBearing, $cameraDirection, $dirtype): bool
    // 判断摄像头监控方向是否与车辆行驶方向匹配
    // 参数：车辆行驶方向、摄像头监控方向、监控类型(dirtype)
    {
        $dirtype = (int) $dirtype;
        // 确保监控类型为整数
        $vehicleBearing = self::normalizeBearing($vehicleBearing);
        // 标准化车辆行驶方向到 [0°, 360°]

        if ($dirtype === 2) {
            // dirtype=2：全向监控
            return true;
            // 全向监控，任何方向都匹配，直接返回 true
        }

        if ($cameraDirection === null || $cameraDirection === '') {
            // 如果摄像头方向为空
            return $dirtype === 2;
            // 只有全向监控才匹配
        }

        $cameraDirection = self::normalizeBearing($cameraDirection);
        // 标准化摄像头监控方向到 [0°, 360°]

        if ($dirtype === 0) {
            // dirtype=0：单向监控
            return self::getAngleDiff($cameraDirection, $vehicleBearing) <= self::DIR_MATCH_ANGLE;
            // 仅当车辆行驶方向与摄像头朝向偏差 ≤45° 时匹配
        }

        if ($dirtype === 1) {
            // dirtype=1：双向监控
            $reverse = self::normalizeBearing($cameraDirection + 180);
            // 计算摄像头的反方向
            return self::getAngleDiff($cameraDirection, $vehicleBearing) <= self::DIR_MATCH_ANGLE
                || self::getAngleDiff($reverse, $vehicleBearing) <= self::DIR_MATCH_ANGLE;
            // 车辆行驶方向与摄像头朝向或反向偏差 ≤45° 时匹配
        }

        return false;
        // 未知监控类型，返回 false
    }











    /**
     * 根据导航路线过滤指定侧别的设备
     * 
     * @param array $vehicle      ['lat' => float, 'lng' => float, 'bearing' => float]
     * @param array $cameras      摄像头/设备列表
     * @param array $route        导航路线点数组 [['lat' => float, 'lng' => float], ...]
     * @param string $side        侧别：'right'（右侧）| 'left'（左侧）| 'both'（两侧），默认 'right'
     * @param float $maxDistance  最大有效距离（米）
     * @param float $routeBuffer  路线缓冲区宽度（米）
     * @return array
     */
    public static function filterCamerasBySide($vehicle, $cameras, $route, $side = 'right', $maxDistance = null, $routeBuffer = 20)
    {
        if (empty($cameras) || empty($route) || count($route) < 2) {
            return self::filterCamerasInFront($vehicle, $cameras, $maxDistance);
        }

        $vLat = (float) $vehicle['lat'];
        $vLng = (float) $vehicle['lng'];
        $vehicleRoutePos = null;

        $vNearestSeg = self::findNearestSegment($vLat, $vLng, $route);
        if ($vNearestSeg !== null) {
            $vProjection = self::getPointToSegmentProjection(
                $vLat, $vLng,
                $vNearestSeg['start']['latitude'], $vNearestSeg['start']['longitude'],
                $vNearestSeg['end']['latitude'], $vNearestSeg['end']['longitude']
            );
            $vehicleRoutePos = [
                'segment_index' => $vNearestSeg['index'],
                'offset' => $vProjection['offset']
            ];
        }

        $result = [];
        $nomatch = [];
        foreach ($cameras as $camera) {
            $matched = self::matchCameraBySide($camera, $route, $side, $maxDistance, $routeBuffer, $vehicleRoutePos);
            if (!empty($matched['code'])) {
                $result[] = $matched['camera'];
            }else{
                $nomatch[] = ['result'=>$matched,'camera'=>$camera];
            }
        }
        usort($result, static function ($a, $b) {
            $distA = isset($a['distance']) ? (int) round($a['distance']) : PHP_INT_MAX;
            $distB = isset($b['distance']) ? (int) round($b['distance']) : PHP_INT_MAX;
            return $distA <=> $distB;
        });

        return ['result'=>$result,'no'=>$nomatch];
    }

    private static function matchCameraBySide($camera, $route, $side, $maxDistance, $routeBuffer, $vehicleRoutePos = null)
    {
        $pLat = (float) $camera['latitude'];
        $pLng = (float) $camera['longitude'];

        $distance = $camera['distance'];

        if ($maxDistance !== null && $distance > $maxDistance) {
            // 如果设置了最大距离限制，且实际距离超过限制
            return ['code'=>0,'reason'=>'exceed_max_distance'];
        }

        $nearestSegment = self::findNearestSegment($pLat, $pLng, $route);
        // 查找离摄像头最近的路线线段，返回线段信息（索引、距离、方位角等）
        if ($nearestSegment === null) {
            // 如果未找到最近线段（路线数据异常）
            return ['code'=>0,'nearestSegment'=>$nearestSegment,'reason'=>'路线数据异常'];
        }

        $distToRoute = $nearestSegment['distance'];
        // 获取摄像头到最近路线段的垂直距离
        if ($distToRoute > $routeBuffer) {
            // 如果距离超过路线缓冲区宽度（默认50米）
            return ['code'=>0,'nearestSegment'=>$nearestSegment,'distToRoute'=>$distToRoute,'reason'=>'超过路线缓冲区'];
        }

        $segmentBearing = $nearestSegment['bearing'];

        $dirtype = (int) ($camera['dirtype'] ?? 0);
        $cameraDirection = $camera['direction'] ?? null;
        if (!self::matchesMonitoringDirection($segmentBearing, $cameraDirection, $dirtype)) {
            return ['code'=>0,'nearestSegment'=>$nearestSegment,'Direction'=>false,'reason'=>'不在同方向'];
        }

        $camera['distance'] = (int) round($distance);
        $camera['match_latitude']  = $pLat;
        $camera['match_longitude'] = $pLng;
        $camera['distance_to_route'] = (int) round($distToRoute);
        $camera['nearestSegment'] = $nearestSegment;

        if ($vehicleRoutePos !== null) {
            $cProjection = self::getPointToSegmentProjection(
                $pLat, $pLng,
                $nearestSegment['start']['latitude'], $nearestSegment['start']['longitude'],
                $nearestSegment['end']['latitude'], $nearestSegment['end']['longitude']
            );
            $cameraRoutePos = [
                'segment_index' => $nearestSegment['index'],
                'offset' => $cProjection['offset']
            ];
            $navDistance = self::getRouteDistance($vehicleRoutePos, $cameraRoutePos, $route);
            if ($navDistance <= 0) {
                return ['code'=>0,'reason'=>'behind_vehicle','reason'=>'累计距离异常'];
            }

            $camera['direct_distance'] = $camera['distance'];
            $camera['distance'] = (int) round($navDistance);
        } else {
            $camera['direct_distance'] = $camera['distance'];
            
        }

        return ['code'=>1,'camera'=>$camera];
    }































    public static function haversineDistance($lat1, $lng1, $lat2, $lng2): float
    // 使用 Haversine 公式计算两点间的球面距离（米）
    // 参数：起点经纬度(lat1,lng1)，终点经纬度(lat2,lng2)
    {
        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        // 计算纬度差并转换为弧度
        $dLng = deg2rad((float) $lng2 - (float) $lng1);
        // 计算经度差并转换为弧度
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($dLng / 2) ** 2;
        // Haversine 公式核心计算：a = sin²(Δφ/2) + cos φ1 ⋅ cos φ2 ⋅ sin²(Δλ/2)
        return 2 * self::EARTH_RADIUS * asin(min(1.0, sqrt($a)));
        // 返回距离：c = 2 ⋅ atan2(√a, √(1−a))，乘以地球半径得到米数
        // min(1.0, sqrt($a)) 处理浮点数精度问题，避免 asin() 参数超出 [-1, 1]
    }

    public static function getCrossTrackDistance($distance, $bearingToPoint, $vehicleBearing): float
    // 计算点到航线的横向距离（米）
    // 参数：直线距离、到点的方位角、车辆行驶方向
    {
        $angleDiff = deg2rad($bearingToPoint - $vehicleBearing);
        // 计算方位角差并转换为弧度
        $distanceRad = $distance / self::EARTH_RADIUS;
        // 将距离转换为弧度（除以地球半径）
        return abs(asin(sin($distanceRad) * sin($angleDiff)) * self::EARTH_RADIUS);
        // 使用球面三角学公式计算横向距离：dxt = asin(sin(d/R) * sin(θt−θ)) * R
    }

    private static function getBearingFast($lat1, $lng1, $lat2, $lng2): float
    // 快速计算从起点到终点的方位角（度数）
    // 参数：起点经纬度(度数)，终点经纬度(度数)
    {
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $y = sin($lng2 - $lng1) * cos($lat2);
        $x = cos($lat1) * sin($lat2)
            - sin($lat1) * cos($lat2) * cos($lng2 - $lng1);

        $bearing = atan2($y, $x);
        $bearing = rad2deg($bearing);
        return fmod(($bearing + 360), 360);
    }

    private static function getAngleDiff($angle1, $angle2): float
    // 计算两个角度之间的最短差值（考虑 360° 循环）
    // 参数：两个角度值
    {
        $diff = fmod(abs((float) $angle1 - (float) $angle2), 360);
        // 计算角度差的绝对值并取模 360
        return $diff > 180 ? 360 - $diff : $diff;
        // 如果差值大于 180，取补角（最短路径），例如 350° 到 10° 的最短差是 20°
    }

    private static function normalizeBearing($bearing): float
    // 将任意角度标准化到 [0°, 360°] 范围内
    // 参数：任意角度值
    {
        return fmod(fmod((float) $bearing, 360.0) + 360.0, 360.0);
        // 先取模 360，再加 360，再取模 360，确保负数也能正确转换
        // 例如：-45° → 315°，405° → 45°
    }

    

    /**
     * 计算点到折线的最短距离（米）
     * 遍历折线上的每一段线段，取最短距离
     */
    public static function getDistanceToPolyline($pointLat, $pointLng, $route): float
    // 计算点到折线的最短距离（米）
    // 参数：点的经纬度，折线点数组
    {
        $minDistance = PHP_FLOAT_MAX;
        // 初始化最小距离为浮点数最大值

        for ($i = 0; $i < count($route) - 1; $i++) {
            // 遍历折线上的每一段线段
            $start = $route[$i];
            // 当前线段起点
            $end = $route[$i + 1];
            // 当前线段终点

            $dist = self::getDistanceToSegment($pointLat, $pointLng, $start['latitude'], $start['longitude'], $end['latitude'], $end['longitude']);
            // 计算点到当前线段的距离
            if ($dist < $minDistance) {
                // 如果当前距离更小
                $minDistance = $dist;
                // 更新最小距离
            }
        }

        return $minDistance;
        // 返回点到折线的最短距离
    }

    /**
     * 计算点到线段的最短距离（米）
     * 使用投影法：将点投影到线段上，如果投影点在线段内则返回垂直距离，否则返回到最近端点的距离
     */
    public static function getDistanceToSegment($px, $py, $ax, $ay, $bx, $by): float
    // 计算点到线段的最短距离（米）
    // 参数：点 P(px,py)，线段端点 A(ax,ay) 和 B(bx,by)
    {
        $px = (float) $px;
        // 确保点 P 纬度为浮点数
        $py = (float) $py;
        // 确保点 P 经度为浮点数
        $ax = (float) $ax;
        $ay = (float) $ay;
        $bx = (float) $bx;
        $by = (float) $by;

        $dAB = self::haversineDistance($ax, $ay, $bx, $by);
        // 计算线段 AB 的长度
        if ($dAB < 3) {
            // 如果线段 AB 长度小于 0.001 米（起点终点几乎重合）
            return self::haversineDistance($px, $py, $ax, $ay);
            // 直接返回点 P 到点 A 的距离
        }

        $dAP = self::haversineDistance($ax, $ay, $px, $py);
        // 计算点 P 到点 A 的距离
        $dBP = self::haversineDistance($bx, $by, $px, $py);
        // 计算点 P 到点 B 的距离

        if ($dAP <= $dAB && $dBP <= $dAB) {
            // 如果点 P 在以 A 和 B 为圆心、AB 为半径的圆内（投影在线段内）
            $bearingAP = self::getBearingFast($ax, $ay, $px, $py);
            // 计算从 A 到 P 的方位角
            $bearingAB = self::getBearingFast($ax, $ay, $bx, $by);
            // 计算从 A 到 B 的方位角
            return abs(asin(sin($dAP / self::EARTH_RADIUS) * sin(deg2rad($bearingAP - $bearingAB))) * self::EARTH_RADIUS);
            // 使用球面三角学计算点到线段的垂直距离
        }

        return min($dAP, $dBP);
        // 投影在线段外，返回点到最近端点的距离
    }

    public static function getSignedCrossTrack($distance, $bearingToPoint, $vehicleBearing): float
    {
        $angleDiff = deg2rad($bearingToPoint - $vehicleBearing);
        $distanceRad = $distance / self::EARTH_RADIUS;
        return asin(sin($distanceRad) * sin($angleDiff)) * self::EARTH_RADIUS;
    }

    public static function findNearestSegment($pointLat, $pointLng, $route): ?array
    {
        $minDistance = PHP_FLOAT_MAX;
        $nearestSegment = null;

        for ($i = 0; $i < count($route) - 1; $i++) {
            $start = $route[$i];
            $end = $route[$i + 1];

            $dist = self::getDistanceToSegment($pointLat, $pointLng, $start['latitude'], $start['longitude'], $end['latitude'], $end['longitude']);
            if ($dist < $minDistance) {
                $minDistance = $dist;
                $segmentBearing = self::getBearingFast($start['latitude'], $start['longitude'], $end['latitude'], $end['longitude']);
                $nearestSegment = [
                    'index' => $i,
                    'distance' => $dist,
                    'bearing' => $segmentBearing,
                    'start' => $start,
                    'end' => $end
                ];
            }
        }

        return $nearestSegment;
    }

    /**
     * 预计算路线段元数据（仅 speedLimit 路径使用）
     * 一次性计算每段的起终点、长度与方位角，避免对每个候选路段重复 haversine/getBearingFast。
     *
     * @param array $route 路线点数组 [['latitude'=>..,'longitude'=>..], ...]
     * @return array [['slat','slng','elat','elng','len','bearing'], ...]
     */
    private static function buildRouteSegments($route): array
    {
        $segments = [];
        $count = count($route);
        for ($i = 0; $i < $count - 1; $i++) {
            $sLat = (float) $route[$i]['latitude'];
            $sLng = (float) $route[$i]['longitude'];
            $eLat = (float) $route[$i + 1]['latitude'];
            $eLng = (float) $route[$i + 1]['longitude'];
            $segments[] = [
                'slat'    => $sLat,
                'slng'    => $sLng,
                'elat'    => $eLat,
                'elng'    => $eLng,
                'len'     => self::haversineDistance($sLat, $sLng, $eLat, $eLng),
                'bearing' => self::getBearingFast($sLat, $sLng, $eLat, $eLng),
            ];
        }
        return $segments;
    }

    /**
     * 点到线段最短距离（复用 buildRouteSegments 预计算的段长度与方位角）
     * 与 getDistanceToSegment 等价，但省去每段重复的 haversine(A,B) 与 getBearingFast(A,B)。
     */
    private static function distanceToSegmentFast($px, $py, $seg): float
    {
        $px = (float) $px;
        $py = (float) $py;

        if ($seg['len'] < 3) {
            return self::haversineDistance($px, $py, $seg['slat'], $seg['slng']);
        }

        $dAP = self::haversineDistance($seg['slat'], $seg['slng'], $px, $py);
        $dBP = self::haversineDistance($seg['elat'], $seg['elng'], $px, $py);

        if ($dAP <= $seg['len'] && $dBP <= $seg['len']) {
            $bearingAP = self::getBearingFast($seg['slat'], $seg['slng'], $px, $py);
            return abs(asin(sin($dAP / self::EARTH_RADIUS) * sin(deg2rad($bearingAP - $seg['bearing']))) * self::EARTH_RADIUS);
        }

        return min($dAP, $dBP);
    }

    /**
     * 查找最近路线段（复用 buildRouteSegments 预计算结果）
     * 返回结构与 findNearestSegment 一致：['index','distance','bearing','start','end']
     */
    private static function findNearestSegmentFast($pointLat, $pointLng, $segments): ?array
    {
        $minDistance = PHP_FLOAT_MAX;
        $nearest = null;

        foreach ($segments as $i => $seg) {
            $dist = self::distanceToSegmentFast($pointLat, $pointLng, $seg);
            if ($dist < $minDistance) {
                $minDistance = $dist;
                $nearest = [
                    'index'    => $i,
                    'distance' => $dist,
                    'bearing'  => $seg['bearing'],
                    'start'    => ['latitude' => $seg['slat'], 'longitude' => $seg['slng']],
                    'end'      => ['latitude' => $seg['elat'], 'longitude' => $seg['elng']],
                ];
            }
        }

        return $nearest;
    }

    /**
     * 计算点到线段的投影位置
     * 返回投影点坐标、沿线段起点的偏移距离、是否在线段内
     *
     * @param float $pLat  点纬度
     * @param float $pLng  点经度
     * @param float $aLat  线段起点纬度
     * @param float $aLng  线段起点经度
     * @param float $bLat  线段终点纬度
     * @param float $bLng  线段终点经度
     * @return array ['lat', 'lng', 'offset', 'on_segment']
     */
    public static function getPointToSegmentProjection($pLat, $pLng, $aLat, $aLng, $bLat, $bLng): array
    {
        $aLat = (float) $aLat;
        $aLng = (float) $aLng;
        $bLat = (float) $bLat;
        $bLng = (float) $bLng;
        $pLat = (float) $pLat;
        $pLng = (float) $pLng;

        $dAB = self::haversineDistance($aLat, $aLng, $bLat, $bLng);

        if ($dAB < 0.001) {
            return ['lat' => $aLat, 'lng' => $aLng, 'offset' => 0, 'on_segment' => true];
        }

        $dAP = self::haversineDistance($aLat, $aLng, $pLat, $pLng);
        $dBP = self::haversineDistance($bLat, $bLng, $pLat, $pLng);

        $bearingAB = self::getBearingFast($aLat, $aLng, $bLat, $bLng);
        $bearingAP = self::getBearingFast($aLat, $aLng, $pLat, $pLng);
        $angleDiff = deg2rad($bearingAP - $bearingAB);

        $offset = $dAP * cos($angleDiff);

        if ($offset < 0) {
            return ['lat' => $aLat, 'lng' => $aLng, 'offset' => 0, 'on_segment' => false];
        }
        if ($offset > $dAB) {
            return ['lat' => $bLat, 'lng' => $bLng, 'offset' => $dAB, 'on_segment' => false];
        }

        $projection = self::getPointAtDistance($aLat, $aLng, $bearingAB, $offset);

        return [
            'lat' => $projection['lat'],
            'lng' => $projection['lng'],
            'offset' => $offset,
            'on_segment' => true
        ];
    }

    /**
     * 根据起点、方位角、距离计算目标点坐标
     *
     * @param float $lat     起点纬度
     * @param float $lng     起点经度
     * @param float $bearing 方位角（度）
     * @param float $distance 距离（米）
     * @return array ['lat', 'lng']
     */
    private static function getPointAtDistance($lat, $lng, $bearing, $distance): array
    {
        $R = self::EARTH_RADIUS;
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);
        $bearingRad = deg2rad($bearing);
        $distRad = $distance / $R;

        $destLat = asin(
            sin($latRad) * cos($distRad) +
            cos($latRad) * sin($distRad) * cos($bearingRad)
        );

        $destLng = $lngRad + atan2(
            sin($bearingRad) * sin($distRad) * cos($latRad),
            cos($distRad) - sin($latRad) * sin($destLat)
        );

        return [
            'lat' => rad2deg($destLat),
            'lng' => rad2deg($destLng)
        ];
    }

    /**
     * 计算沿导航路线从车辆位置到摄像头位置的累计距离
     *
     * @param array $vehicleRoutePos 车辆在路线上的位置 ['segment_index' => int, 'offset' => float]
     * @param array $cameraRoutePos  摄像头在路线上的位置 ['segment_index' => int, 'offset' => float]
     * @param array $route           导航路线点数组
     * @return float 累计导航距离（米），若摄像头在车辆后方返回 0
     */
    public static function getRouteDistance($vehicleRoutePos, $cameraRoutePos, $route): float
    {
        $vIndex = (int) $vehicleRoutePos['segment_index'];
        $vOffset = (float) $vehicleRoutePos['offset'];
        $cIndex = (int) $cameraRoutePos['segment_index'];
        $cOffset = (float) $cameraRoutePos['offset'];

        if ($cIndex < $vIndex) {
            return 0;
        }

        if ($cIndex === $vIndex) {
            return max(0, $cOffset - $vOffset);
        }

        $distance = 0;

        $vSegEnd = $route[$vIndex + 1];
        $distance += self::haversineDistance(
            $route[$vIndex]['latitude'], $route[$vIndex]['longitude'],
            $vSegEnd['latitude'], $vSegEnd['longitude']
        ) - $vOffset;

        for ($i = $vIndex + 1; $i < $cIndex; $i++) {
            $distance += self::haversineDistance(
                $route[$i]['latitude'], $route[$i]['longitude'],
                $route[$i + 1]['latitude'], $route[$i + 1]['longitude']
            );
        }

        $distance += $cOffset;

        return $distance;
    }

    /**
     * 解析限速路段相对「行驶方向」的适用限速值
     *
     * 路段正向 = 起点(start) → 终点(end)；travel_direction: F=仅正向, T=仅反向, B=双向, N=封闭。
     * 正向取 from_speed_limit_kmh，反向取 to_speed_limit_kmh。
     *
     * @param float $travelBearing 行驶方向（车辆航向，或路线段方位角）
     * @param array $limit         限速路段记录
     * @return array|null ['speed_limit'=>int, 'direction'=>'F'|'T', 'seg_bearing'=>int] 或 null（不匹配）
     */
    private static function resolveSpeedLimitDirection($travelBearing, $limit)
    {
        $travelDirection = $limit['travel_direction'] ?? null;
        if ($travelDirection === null || $travelDirection === '' || $travelDirection === 'N') {
            return null; // 封闭/缺失，不匹配
        }

        $startLat = (float) ($limit['start_lat'] ?? 0);
        $startLng = (float) ($limit['start_lon'] ?? 0);
        $endLat   = (float) ($limit['end_lat'] ?? 0);
        $endLng   = (float) ($limit['end_lon'] ?? 0);

        // 路段几何方位角（起点 → 终点）
        $segBearing = self::getBearingFast($startLat, $startLng, $endLat, $endLng);
        $dForward = self::getAngleDiff($travelBearing, $segBearing);
        $dReverse = self::getAngleDiff($travelBearing, self::normalizeBearing($segBearing + 180));
        $isForward = $dForward <= $dReverse;
        $diff = $isForward ? $dForward : $dReverse;

        if ($isForward) {
            if (!in_array($travelDirection, ['F', 'B'], true)) {
                return null;
            }
            $speedLimit = $limit['from_speed_limit_kmh'] ?? null;
            $dirCode = 'F';
        } else {
            if (!in_array($travelDirection, ['T', 'B'], true)) {
                return null;
            }
            $speedLimit = $limit['to_speed_limit_kmh'] ?? null;
            $dirCode = 'T';
        }

        if ($speedLimit === null || $speedLimit === '') {
            return null; // 该方向无限速（999 已置 NULL）
        }
        if ($diff > self::DIR_MATCH_ANGLE) {
            return null; // 行驶方向与路段方向偏差过大（>30°）
        }

        return [
            'speed_limit' => (int) $speedLimit,
            'direction'   => $dirCode,
            'seg_bearing' => (int) round($segBearing),
        ];
    }

    /**
     * 计算车辆相对限速路段（entry→exit，已按行驶方向定向）的三段式位置
     *
     * along 为车辆在 entry→exit 方向上的有符号投影距离：
     *   along < 0            → 未进入（entry 在前方）
     *   0 <= along <= 路段长 → 路段中
     *   along > 路段长       → 已通过
     *
     * @return array ['state'=>0未进入|1路段中|2已通过, 'along'=>float, 'lateral'=>float, 'seg_len'=>float]
     */
    private static function classifySpeedLimitPosition($vLat, $vLng, $aLat, $aLng, $bLat, $bLng)
    {
        $aLat = (float) $aLat;
        $aLng = (float) $aLng;
        $bLat = (float) $bLat;
        $bLng = (float) $bLng;

        $segLen = self::haversineDistance($aLat, $aLng, $bLat, $bLng);
        $dA     = self::haversineDistance($vLat, $vLng, $aLat, $aLng);

        if ($segLen < 0.001) {
            // 起终点几乎重合：视为未进入，距离为到该点距离
            return ['state' => 0, 'along' => 0.0, 'lateral' => $dA, 'seg_len' => 0.0];
        }

        $bearingAB = self::getBearingFast($aLat, $aLng, $bLat, $bLng);
        $bearingAV = self::getBearingFast($aLat, $aLng, $vLat, $vLng);

        // 有符号最短夹角 [-180, 180]
        $delta = $bearingAV - $bearingAB;
        if ($delta > 180) {
            $delta -= 360;
        } elseif ($delta < -180) {
            $delta += 360;
        }

        $along   = $dA * cos(deg2rad($delta));   // 沿 entry→exit 的有符号投影距离
        $lateral = $dA * sin(deg2rad($delta));   // 有符号横向偏移

        if ($along < 0) {
            $state = 0; // 未进入：entry 在前方
        } elseif ($along <= $segLen) {
            $state = 1; // 路段中
        } else {
            $state = 2; // 已通过
        }

        return [
            'state'   => $state,
            'along'   => $along,
            'lateral' => $lateral,
            'seg_len' => $segLen,
        ];
    }

    /**
     * 方向解析 + 三段式位置组装（供有/无路线两个分支复用）
     *
     * 方向匹配（含对面车道剔除）由 resolveSpeedLimitDirection 保证；
     * 这里再按行驶方向把路段定向为 entry（车辆先到达的一端）→ exit（后到达的一端）。
     *
     * @param float $travelBearing 行驶方向（车辆航向，或路线段方位角）
     * @param array $limit         限速路段记录
     * @return array|null 命中返回组装后的结果，否则 null
     */
    private static function resolveSpeedLimitSegment($travelBearing, $vLat, $vLng, $limit)
    {
        $resolved = self::resolveSpeedLimitDirection($travelBearing, $limit); // 对面车道在此被剔除
        if ($resolved === null) {
            return null;
        }

        $startLat = (float) ($limit['start_lat'] ?? 0);
        $startLng = (float) ($limit['start_lon'] ?? 0);
        $endLat   = (float) ($limit['end_lat'] ?? 0);
        $endLng   = (float) ($limit['end_lon'] ?? 0);

        if ($resolved['direction'] === 'F') {
            // 正向：entry=起点, exit=终点
            $entry = ['lat' => $startLat, 'lng' => $startLng];
            $exit  = ['lat' => $endLat,   'lng' => $endLng];
        } else {
            // 反向：entry=终点, exit=起点
            $entry = ['lat' => $endLat,   'lng' => $endLng];
            $exit  = ['lat' => $startLat, 'lng' => $startLng];
        }

        $pos = self::classifySpeedLimitPosition(
            $vLat, $vLng,
            $entry['lat'], $entry['lng'],
            $exit['lat'],  $exit['lng']
        );

        return $resolved + [
            'entry_lat' => $entry['lat'],
            'entry_lng' => $entry['lng'],
            'exit_lat'  => $exit['lat'],
            'exit_lng'  => $exit['lng'],
            'state'     => $pos['state'],
            'along'     => $pos['along'],
            'lateral'   => $pos['lateral'],
            'seg_len'   => $pos['seg_len'],
        ];
    }

    /**
     * 道路限速过滤（无路线）：仅保留车辆前方且行驶方向匹配的限速路段
     */
    public static function filterSpeedLimitsInFront($vehicle, $limits, $maxDistance = null)
    {
        if (empty($limits)) {
            return ['result' => [], 'no' => []];
        }

        $vLat     = (float) $vehicle['lat'];
        $vLng     = (float) $vehicle['lng'];
        $vBearing = self::normalizeBearing($vehicle['bearing']);

        $result = [];
        $no = [];
        foreach ($limits as $limit) {
            $matched = self::matchSpeedLimit($vLat, $vLng, $vBearing, $limit, $maxDistance);
            if ($matched !== null) {
                $result[] = $matched;
            } else {
                $no[] = $limit;
            }
        }

        // 排序：路段中(in_segment=1)优先，其余按 distance 由近到远，最后用 id 唯一键兜底保证确定性
        usort($result, static function ($a, $b) {
            $inA = (int) ($a['in_segment'] ?? 0);
            $inB = (int) ($b['in_segment'] ?? 0);
            if ($inA !== $inB) {
                return $inB <=> $inA;
            }
            $distA = isset($a['distance']) ? (int) round($a['distance']) : PHP_INT_MAX;
            $distB = isset($b['distance']) ? (int) round($b['distance']) : PHP_INT_MAX;
            if ($distA !== $distB) {
                return $distA <=> $distB;
            }
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        return ['result' => $result, 'no' => $no];
    }

    /**
     * 限速路段横向偏移容差（米），随位置状态自适应。
     *
     * state=1（路段中）：固定 SPEED_LIMIT_LATERAL_BUFFER —— 车辆已在路上，容曲线近似误差。
     * state=0（接近中）：max(MIN, |along|·tan(角度容差)) —— 越远越宽、越近越紧，
     *   既容忍远距离弯道的直线近似误差，又能在近距离剔除从侧面接近的旁路。
     */
    private static function speedLimitLateralLimit($state, $along)
    {
        if ($state === 1) {
            return self::SPEED_LIMIT_LATERAL_BUFFER;
        }
        return max(self::SPEED_LIMIT_LATERAL_MIN, abs($along) * tan(deg2rad(self::SPEED_LIMIT_APPROACH_ANGLE)));
    }

    /**
     * 单个限速路段匹配（无路线）：方向 + 三段式位置 + 横向容差
     *
     * state=0 未进入 → distance 为到起点(entry)距离
     * state=1 路段中 → distance 为到终点(exit)距离
     * state=2 已通过 → 不返回
     */
    private static function matchSpeedLimit($vLat, $vLng, $vBearing, $limit, $maxDistance)
    {
        $base = self::resolveSpeedLimitSegment($vBearing, $vLat, $vLng, $limit);
        if ($base === null) {
            return null; // 方向不匹配（含对面车道）
        }

        if ($base['state'] === 2) {
            return null; // 已通过该路段
        }

        if (abs($base['lateral']) > self::speedLimitLateralLimit($base['state'], $base['along'])) {
            return null; // 横向偏移超出自适应容差，不在该路段上
        }

        if ($base['state'] === 0) {
            // 未进入：距离限速起始点
            $targetLat = $base['entry_lat'];
            $targetLng = $base['entry_lng'];
        } else {
            // 路段中：距离限速终点
            $targetLat = $base['exit_lat'];
            $targetLng = $base['exit_lng'];
        }

        $distance = self::haversineDistance($vLat, $vLng, $targetLat, $targetLng);
        if ($maxDistance !== null && $distance > $maxDistance) {
            return null;
        }

        $limit['distance']        = (int) round($distance);
        $limit['in_segment']      = $base['state'] === 1 ? 1 : 0;
        $limit['speed_limit']     = $base['speed_limit'];
        $limit['direction']       = $base['direction'];
        $limit['seg_bearing']     = $base['seg_bearing'];
        $limit['match_latitude']  = $targetLat;
        $limit['match_longitude'] = $targetLng;

        unset($limit['nearest_distance'], $limit['start_distance'], $limit['end_distance']);

        return $limit;
    }

    /**
     * 道路限速过滤（有路线）：按导航路线（缓冲区 + 沿路线距离 + 行驶方向）过滤
     */
    public static function filterSpeedLimitsBySide($vehicle, $limits, $route, $maxDistance = null, $routeBuffer = 20)
    {
        if (empty($limits) || empty($route) || count($route) < 2) {
            return self::filterSpeedLimitsInFront($vehicle, $limits, $maxDistance);
        }

        $vLat = (float) $vehicle['lat'];
        $vLng = (float) $vehicle['lng'];

        // 预计算路线段元数据，供后续候选路段复用，避免重复 haversine/方位角计算
        $segs = self::buildRouteSegments($route);

        $vehicleRoutePos = null;
        $vNearestSeg = self::findNearestSegmentFast($vLat, $vLng, $segs);
        if ($vNearestSeg !== null) {
            $vProjection = self::getPointToSegmentProjection(
                $vLat, $vLng,
                $vNearestSeg['start']['latitude'], $vNearestSeg['start']['longitude'],
                $vNearestSeg['end']['latitude'],   $vNearestSeg['end']['longitude']
            );
            $vehicleRoutePos = [
                'segment_index' => $vNearestSeg['index'],
                'offset'        => $vProjection['offset']
            ];
        }

        $result = [];
        $no = [];
        foreach ($limits as $limit) {
            $matched = self::matchSpeedLimitBySide($vLat, $vLng, $limit, $route, $segs, $maxDistance, $routeBuffer, $vehicleRoutePos);
            if (!empty($matched['code'])) {
                $result[] = $matched['limit'];
            } else {
                $no[] = ['result' => $matched, 'limit' => $limit];
            }
        }

        // 排序：路段中(in_segment=1)优先，其余按 distance 由近到远，最后用 id 唯一键兜底保证确定性
        usort($result, static function ($a, $b) {
            $inA = (int) ($a['in_segment'] ?? 0);
            $inB = (int) ($b['in_segment'] ?? 0);
            if ($inA !== $inB) {
                return $inB <=> $inA;
            }
            $distA = isset($a['distance']) ? (int) round($a['distance']) : PHP_INT_MAX;
            $distB = isset($b['distance']) ? (int) round($b['distance']) : PHP_INT_MAX;
            if ($distA !== $distB) {
                return $distA <=> $distB;
            }
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        return ['result' => $result, 'no' => $no];
    }

    /**
     * 单个限速路段匹配（有路线）
     */
    private static function matchSpeedLimitBySide($vLat, $vLng, $limit, $route, $segs, $maxDistance, $routeBuffer, $vehicleRoutePos)
    {
        $startLat = (float) ($limit['start_lat'] ?? 0);
        $startLng = (float) ($limit['start_lon'] ?? 0);

        $nearestSegment = self::findNearestSegmentFast($startLat, $startLng, $segs);
        if ($nearestSegment === null) {
            return ['code' => 0, 'reason' => '路线数据异常'];
        }

        $distToRoute = $nearestSegment['distance'];
        if ($distToRoute > $routeBuffer) {
            return ['code' => 0, 'reason' => '超过路线缓冲区'];
        }

        // 方向匹配使用「路段起点最近路线段方位角」，并完成三段式位置判定
        $base = self::resolveSpeedLimitSegment($nearestSegment['bearing'], $vLat, $vLng, $limit);
        if ($base === null) {
            return ['code' => 0, 'reason' => '不在同方向'];
        }

        if ($base['state'] === 2) {
            return ['code' => 0, 'reason' => '已通过该路段'];
        }

        // 车辆相对限速路段直线的横向偏移校验（剔除旁边道路 / 对向车道）
        if (abs($base['lateral']) > self::speedLimitLateralLimit($base['state'], $base['along'])) {
            return ['code' => 0, 'reason' => '横向偏移超限'];
        }

        if ($base['state'] === 0) {
            // 未进入：目标点为起点(entry)
            $targetLat = $base['entry_lat'];
            $targetLng = $base['entry_lng'];
        } else {
            // 路段中：目标点为终点(exit)
            $targetLat = $base['exit_lat'];
            $targetLng = $base['exit_lng'];
        }

        $directDistance = self::haversineDistance($vLat, $vLng, $targetLat, $targetLng);
        $distance       = $directDistance;

        if ($vehicleRoutePos !== null) {
            $targetSegment = self::findNearestSegmentFast($targetLat, $targetLng, $segs);
            if ($targetSegment === null) {
                return ['code' => 0, 'reason' => '路线数据异常'];
            }
            $tProjection = self::getPointToSegmentProjection(
                $targetLat, $targetLng,
                $targetSegment['start']['latitude'], $targetSegment['start']['longitude'],
                $targetSegment['end']['latitude'],   $targetSegment['end']['longitude']
            );
            $targetRoutePos = [
                'segment_index' => $targetSegment['index'],
                'offset'        => $tProjection['offset']
            ];
            $navDistance = self::getRouteDistance($vehicleRoutePos, $targetRoutePos, $route);
            if ($navDistance > 0) {
                $distance = $navDistance; // 沿路导航距离
            }
        }

        if ($maxDistance !== null && $distance > $maxDistance) {
            return ['code' => 0, 'reason' => 'exceed_max_distance'];
        }

        $limit['distance']          = (int) round($distance);
        $limit['direct_distance']   = (int) round($directDistance);
        $limit['in_segment']        = $base['state'] === 1 ? 1 : 0;
        $limit['speed_limit']       = $base['speed_limit'];
        $limit['direction']         = $base['direction'];
        $limit['seg_bearing']       = $base['seg_bearing'];
        $limit['match_latitude']    = $targetLat;
        $limit['match_longitude']   = $targetLng;
        $limit['distance_to_route'] = (int) round($distToRoute);

        unset($limit['nearest_distance'], $limit['start_distance'], $limit['end_distance']);

        return ['code' => 1, 'limit' => $limit];
    }

}
// 类结束