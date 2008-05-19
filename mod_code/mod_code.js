if(!Array.prototype.indexOf){ // Implemented in JavaScript 1.6
	Array.prototype.indexOf = function(elt /*, from*/){
		var len = this.length;
		var from = Number(arguments[1]) || 0;
		from = from < 0 ? Math.ceil(from) : Math.floor(from);
		if(from < 0){ from += len; }

		for(; from < len; from++){
			if(from in this && this[from] === elt){ return from; }
		}
		return -1;
	};
}

// dp.SyntaxHighlighter 動態載入
var Tdp = {
	Sensor : { // 一些有用數值
		Name : '', // dp.SyntaxHighlighter 作用欄位名稱
		Interval : null, // dp.sh.Brushes Check Interval
		LibLoaded : [] // 載入函式庫
	},
	Alias : { // 別名資料庫
		Cpp : ['cpp', 'c', 'c++'],
		CSharp : ['c#', 'c-sharp', 'csharp'],
		Css : ['css'],
		Delphi : ['delphi', 'pascal'],
		Java : ['java'],
		JScript : ['js', 'jscript', 'javascript'],
		Php : ['php'],
		Python : ['py', 'python'],
		Ruby : ['rb', 'ruby', 'rails', 'ror'],
		Sql : ['sql'],
		Vb : ['vb', 'vb.net'],
		Xml : ['xml', 'html', 'xhtml', 'xslt']
	},
	/* 動態載入 */
	SyntaxHighlighter : function (taName){
		Tdp.Sensor.Name = taName; // 設定作用欄位名
		var tas = document.getElementsByTagName('textarea');
		var sc, tx;
		for(var i = 0, tl = tas.length; i < tl; i++){ // 逐一搜尋欄位
			tx = tas[i];
			if(tx.getAttribute('name') == taName){
				for(var a in Tdp.Alias){ // 逐一搜尋程式類別
					if(Tdp.Alias[a].indexOf(tx.className) != -1){ // 找到
						Tdp.Sensor.LibLoaded.push(a); delete Tdp.Alias[a]; // 推入載入名單
						break;
					}
				}
			}
		}
		var libcount = Tdp.Sensor.LibLoaded.length;
		if(libcount === 0){ return; } // 未使用直接跳出

		var insertnode = document.getElementsByTagName('head')[0];
		for(var j = 0; j < libcount; j++){ // 動態載入
			sc = document.createElement('script');
			sc.type = 'text/javascript';
			sc.src = 'module/shBrush' + Tdp.Sensor.LibLoaded[j] + '.js';
			insertnode.appendChild(sc);
		}
		Tdp.Sensor.Interval = setInterval(Tdp.executeSyntaxHighlighter, 1000); // 設定 Interval
	},
	/* 確定全部載入完成，執行標亮 */
	executeSyntaxHighlighter : function(){
		if(typeof dp==='undefined'){ return false; } // dp Not ready
		for(var lib in Tdp.Sensor.LibLoaded){
			if(typeof dp.sh.Brushes[Tdp.Sensor.LibLoaded[lib]]==='function'){ Tdp.Sensor.LibLoaded.splice(lib, 1); }
		}
		if(Tdp.Sensor.LibLoaded.join('')===''){
			clearInterval(Tdp.Sensor.Interval);
			dp.SyntaxHighlighter.HighlightAll(Tdp.Sensor.Name); // dp.SyntaxHighlighter
		}
	}
};