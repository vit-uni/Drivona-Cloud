var msgEle, loadEle, textEle, wrapper;
var isHideMsg = false;
var isMenuInited = false;

var MENU_TPLS = {
"windows": [
  '<a href="javascript:void(0);"><i class="icon icon-drop" style="margin: 13px 0 0 0;"></i><\/a><\/div>',
  '<div class="toolbar" id="toolbar">',
  '<ul>',
  '<li>',
  '<a href="javascript:void(0);" data-name="switch-screen"><i class="icon icon-screen"></i>切换屏幕<\/a><div class="menu">',
  '<ul id="screen-list">',
  '<\/ul><\/div>',
  '<\/li>',
  '<li>',
  '<a href="javascript:void(0);" data-name="switch-session"><i class="icon icon-session"></i>切换会话<\/a><div class="menu">',
  '<ul id="session-list">',
  '<\/ul><\/div>',
  '<\/li>',
  '<li><a href="javascript:void(0);" data-name="hotkey"><i class="icon icon-hotkey"></i>快捷键<\/a><div class="menu">',
  '<ul>',
  '<li><a href="javascript:void(0)" data-action="hotKey" data-param="[17, 18, 46]">Ctrl+Alt+Delete<\/a><\/li>',
  '<li><a href="javascript:void(0)" data-action="hotKey" data-param="[91, 76]">锁定桌面<\/a><\/li>',
  '<li><a href="javascript:void(0)" data-action="sendSysMessage" data-param="3">注销<\/a><\/li>',
  '<li><a href="javascript:void(0)" data-action="sendSysMessage" data-param="2">重新启动<\/a><\/li>',
  '<li><a href="javascript:void(0)" data-action="sendSysMessage" data-param="1">关机<\/a><\/li>',
  '<\/ul>',
  '<ul>',
  '<li><a href="javascript:void(0)" data-action="hotKey" data-param="[91, 69]">资源管理器<\/a><\/li>',
  '<li><a href="javascript:void(0)" data-action="hotKey" data-param="[17, 16, 27]">任务管理器<\/a><\/li>',
  '<li><a href="javascript:void(0)" data-action="sendSysMessage" data-param="4">CMD<\/a><\/li>',
  '<\/ul>',
  '<\/div>',
  '<\/li>',
  '<\/ul>'
  ].join(''),

 "android": [
    '<a href="javascript:void(0);"><i class="icon icon-drop" style="margin: 13px 0 0 0;"></i><\/a><\/div>',
    '<div class="toolbar" id="toolbar">',
    '<ul>',
    '<li>',
    '<a href="javascript:void(0);" data-name="switch-screen"><i class="icon icon-screen"></i>屏幕<\/a><div class="menu">',
    '<ul id="screen-list">',
    '<\/ul><\/div>',
    '<\/li>',
    '<li>',
    '<a href="javascript:void(0);" data-name="switch-session"><i class="icon icon-session"></i>切换会话<\/a><div class="menu">',
    '<ul id="session-list">',
    '<\/ul><\/div>',
    '<\/li>',
    '<li><a href="javascript:void(0);" data-name="hotkey"><i class="icon icon-hotkey"></i>快捷键<\/a><div class="menu">',
    '<ul>',
    '<li><a href="javascript:void(0)" data-action="sendKeys" data-param="[17, 18, 46]">Ctrl+Alt+Delete<\/a><\/li>',
    '<li><a href="javascript:void(0)" data-action="sendKeys" data-param="[91, 76]">锁定桌面<\/a><\/li>',
    '<li><a href="javascript:void(0)" data-action="sendSysMessage" data-param="3">注销<\/a><\/li>',
    '<li><a href="javascript:void(0)" data-action="sendSysMessage" data-param="2">重新启动<\/a><\/li>',
    '<li><a href="javascript:void(0)" data-action="sendSysMessage" data-param="1">关机<\/a><\/li>',
    '<\/ul>',
    '<ul>',
    '<li><a href="javascript:void(0)" data-action="sendKeys" data-param="[91, 69]">资源管理器<\/a><\/li>',
    '<li><a href="javascript:void(0)" data-action="sendKeys" data-param="[17, 16, 27]">任务管理器<\/a><\/li>',
    '<li><a href="javascript:void(0)" data-action="sendSysMessage" data-param="4">CMD<\/a><\/li>',
    '<\/ul>',
    '<\/div>',
    '<\/li>',
    '<\/ul>'
    ].join('')
  };

window.addEventListener('load', function() {
  msgEle  = document.getElementById('msg');
  loadEle = document.getElementById('loading-animate');
  textEle = document.getElementById('msg-text');
  wrapper = document.getElementById('wrapper');
});

// 适应视图位置
function adjustWrapperPosition() {
  wrapper.style.position = 'absolute';

  var posX = Math.max(0, ~~ ((document.body.offsetWidth - wrapper.offsetWidth) / 2));
  var posY = Math.max(0, ~~ ((document.body.offsetHeight - wrapper.offsetHeight) / 2));

  wrapper.style.left = posX + 'px';
  wrapper.style.top  = posY + 'px';
  //wrapper.style.marginLeft = (-1 * wrapper.offsetWidth / 2) + 'px';
  //wrapper.style.marginTop = (-1 * wrapper.offsetHeight / 2) + 'px';
}

function updateMessage(text, isLoading) {
  if (typeof(isLoading) == 'undefined') {
    isLoading = true;
  }

  msgEle.style.display = '';
  isHideMsg = false;

  textEle.innerHTML = text;
  if (isLoading) {
    loadEle.style.display = 'inline-block';
  } else {
    loadEle.style.display = 'none';
  }
}

function hideMessage() {
  if (isHideMsg) {
    return ;
  }
  msgEle.style.display = 'none';
  isHideMsg = true;
}

function initMenu(platform) {
  if (isMenuInited) {
    return ;
  }

  isMenuInited = true;
  var drop = document.createElement('div');
  drop.id = 'drop';
  drop.className = 'drop';
  drop.innerHTML = MENU_TPLS[platform];

  document.body.insertBefore(drop, document.body.childNodes[0]);

  drop.addEventListener('click', function() {
    drop.style.display = 'none';
    document.getElementById('toolbar').style.display = 'block';
  });
}

function destroyMenu() {
  document.body.removeChild(document.getElementById('drop'));
}