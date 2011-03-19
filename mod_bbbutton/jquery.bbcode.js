/*
 * Plugin jQuery.BBCode
 * Version 0.2 (mod_bbbutton edition)
 *
 * Based on jQuery.BBCode plugin (http://www.kamaikinproject.ru)
 * mod_bbbutton edition by RT (Pixmicat! project)
 */
(function($){
  $.fn.bbcode = function(options){
		// default settings
    var options = $.extend({
      tags: {},
      button_image: false,
      image_url: 'bbicon/'
    },options||{});
    $.fn.bbcode.options = options;
    //  panel 
    var text = '<div id="bbcode_bb_bar">';
    $.each(options.tags, function(key, value) {
      text += '<a href="#" id="'+key+'" title="'+value.desc+'">';
      if(options.button_image){
        text += '<img src="'+options.image_url+key+'.png" />';
      }else{
        text += '['+key+']';
      }
      text += '</a>';
    });
    text += '</div>';
    
    $(this).wrap('<div id="bbcode_container"></div>');
    $("#bbcode_container").prepend(text);
    $("#bbcode_bb_bar a img").css("border", "none");
    var id = '#'+$(this).attr("id");
    var e = $(id).get(0);
    
    $('#bbcode_bb_bar a').click(function() {
      var button_id = $(this).attr("id");
      var start = '['+button_id+']';
      var end = '[/'+button_id+']';

	  var param="";
	  var tag_clicked = eval('$.fn.bbcode.options.tags.'+button_id);
	  if (!!tag_clicked.prompt) {
	     param=prompt(tag_clicked.prompt.prompt,tag_clicked.prompt.def);
		 if (param)
			start='['+button_id+'='+param+']';
	  }
      insert(start, end, e);
      return false;
    });
	}
  function insert(start, end, element) {
    if (document.selection) {
       element.focus();
       sel = document.selection.createRange();
       sel.text = start+sel.text+end;
    } else if (element.selectionStart || element.selectionStart == '0') {
       element.focus();
       var startPos = element.selectionStart;
       var endPos = element.selectionEnd;
       element.value = element.value.substring(0, startPos)+start+element.value.substring(startPos, endPos)+end+element.value.substring(endPos, element.value.length);
    } else {
      element.value += start+end;
    }
  }

})(jQuery)