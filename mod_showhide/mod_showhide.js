if(!Array.prototype.indexOf){ // Inplemented in JavaScript 1.6
	Array.prototype.indexOf = function(item){
		var len = this.length;
		for(var i = 0; i < len; i++){ if(this[i] === item){ return i; } }
		return -1;
	};
}

var TmodShowhide = {
	hideList : [], // 隱藏討論串列表
	isChange : false, // 是否有更動需回存
	/* 載入討論串隱藏列表並實行隱藏 */
	init : function(){
		var t;
		if(location.href.indexOf('.php?res=')!==-1){ return; } // 回應模式不動作
		jQuery('div.threadpost').each(function(){
			var j = jQuery(this).wrap('<div class="threadStructure" id="t'+this.id+'"></div>').parent();
			var replies = [];
			while((j = j.next('.reply')).size() !== 0){ replies.push(j); }
			jQuery(replies).insertAfter(this);
		});
		if(t = getCookie('hideList')){
			//alert('getCookie');
			TmodShowhide.hideList = t.split(',');
			jQuery('div.threadStructure').each(function(){
				//alert('loop:'+this.id);
				if(TmodShowhide.hideList.indexOf(this.id)!==-1){ jQuery(this).hide(); } // 隱藏討論串
			});
		}
		// 加上控制按鈕
		jQuery('div.threadStructure').each(function(){
			jQuery(this).before('[<a href="javascript:void(0);" onclick="TmodShowhide.switchThread(\''+this.id+'\');" title="Hide/Show this thread">+ / -</a>]<br />');
		});
		//alert('OK:'+TmodShowhide.hideList);
	},
	/* 切換文章顯示與否 */
	switchThread : function(no){
		var t;
		TmodShowhide.isChange = true;
		if((t = TmodShowhide.hideList.indexOf(no))!==-1){
			TmodShowhide.hideList.splice(t, 1);
			jQuery('div.threadStructure#'+no).show('slow');
		}else{
			TmodShowhide.hideList.push(no);
			jQuery('div.threadStructure#'+no).hide('slow');
		}
		//alert('s:'+TmodShowhide.hideList);
	},
	/* 回存 */
	unload : function(){
		if(TmodShowhide.isChange){ setCookie('hideList', TmodShowhide.hideList.join(',')); }
		//alert('bye');
	}
};

jQuery(TmodShowhide.init); // 註冊載入事件
jQuery(window).unload(TmodShowhide.unload); // 註冊結束事件