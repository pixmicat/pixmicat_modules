/*
 * Plugin jQuery.BBCode
 * Version 0.2 
 *
 * Based on jQuery.BBCode plugin (http://www.kamaikinproject.ru)
 */
(function($){
  $.fn.bbcode = function(options){
    // default settings
    var options = $.extend({
      tag_bold: true,
      tag_italic: true,
      tag_underline: true,
      tag_link: true,
      tag_image: false,
      button_image: false,
      image_url: 'bbimage/'
    },options||{});
    //  panel 
    var text = '<div id="bbcode_bb_bar">'
    if(options.tag_bold){
      text = text + '<a href="#" id="b" title="">';
      if(options.button_image){
        text = text + '<img src="' + options.image_url + 'bold.png" />';
      }else{
        text = text + ' 粗(B) ';
      }
      text = text + '</a>';
    }
    if(options.tag_strikethrough){
      text = text + '<a href="#" id="d" title="">';
      if(options.button_image){
        text = text + '<img src="' + options.image_url + 'strikethrough.png" />';
      }else{
        text = text + ' 刪(D) ';
      }
      text = text + '</a>';
    }
    if(options.tag_italic){
      text = text + '<a href="#" id="i" title="">';
      if(options.button_image){
        text = text + '<img src="' + options.image_url + 'italic.png" />';
      }else{
        text = text + ' 斜(I) ';
      }
      text = text + '</a>';
    }
    if(options.tag_underline){
      text = text + '<a href="#" id="u" title="">';
      if(options.button_image){
        text = text + '<img src="' + options.image_url + 'underline.png" />';
      }else{
        text = text + ' 底(U) ';
      }
      text = text + '</a>';
    }
    if(options.tag_link){
      text = text + '<a href="#" id="url" title="">';
      if(options.button_image){
        text = text + '<img src="' + options.image_url + 'link.png" />';
      }else{
        text = text + ' 連(L) ';
      }
      text = text + '</a>';
    }
    if(options.tag_image){
      text = text + '<a href="#" id="img" title="">';
      if(options.button_image){
        text = text + '<img src="' + options.image_url + 'image.png" />';
      }else{
        text = text + ' 圖 ';
      }
      text = text + '</a>';
    }
    text = text + '</div>';
    
    $(this).wrap('<div id="bbcode_container"></div>');
    $("#bbcode_container").prepend(text);
    $("#bbcode_bb_bar a img").css("border", "none");
    var id = '#' + $(this).attr("id");
    var e = $(id).get(0);
    
    $('#bbcode_bb_bar a').click(function() {
      var button_id = $(this).attr("id");
      var start = '['+button_id+']';
      var end = '[/'+button_id+']';

    var param="";
    if (button_id=='img')
    {
       param=prompt("Enter image URL","http://");
     if (param)
      start+=param;
     }
    else if (button_id=='url')
    {
      param=prompt("Enter URL","http://");
      if (param) 
        start = '[url=' + param + ']';
     }
      insert(start, end, e);
      return false;
    });
  }
  function insert(start, end, element) {
    if (document.selection) {
       element.focus();
       sel = document.selection.createRange();
       sel.text = start + sel.text + end;
    } else if (element.selectionStart || element.selectionStart == '0') {
       element.focus();
       var startPos = element.selectionStart;
       var endPos = element.selectionEnd;
       element.value = element.value.substring(0, startPos) + start + element.value.substring(startPos, endPos) + end + element.value.substring(endPos, element.value.length);
    } else {
      element.value += start + end;
    }
  }
 
// hotkeys 
$(document).keyup(function (e) 
{ 
  if(e.which == 17) isCtrl=false; }).keydown(function (e) 
    { if(e.which == 17) isCtrl=true; 
      if (e.which == 66 && isCtrl == true) // CTRL + B, bold
      { 
        $("#b").click();
        return false;
      }
      else if (e.which == 'D'.charCodeAt(0) && isCtrl == true) // CTRL + D, Strike Through
      { 
        $("#d").click();
        return false;
      } 
      else if (e.which == 'I'.charCodeAt(0)  && isCtrl == true) // CTRL + I, italic
      { 
        $("#i").click();
        return false;
      } 
      else if (e.which == 'L'.charCodeAt(0)  && isCtrl == true) // CTRL + L, Link
      { 
        $("#url").click();
        return false;
      } 
      else if (e.which == 'U'.charCodeAt(0)  && isCtrl == true) // CTRL + U, underline
      { 
        $("#u").click();
        return false;
      }
    })  
})(jQuery)
