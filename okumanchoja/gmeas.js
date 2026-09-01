/* gMeas: 計測dataLayer標準実装 v1.1 億万鳥者版（2026/09/01）
   KAMAKURA gMeas v1（GROWX計測標準）準拠。差分は次の3点のみ：
     1) 仮想page_viewは実装しない（物理3ページのためGA4自動page_viewに任せる。二重計上防止）
     2) 店舗識別を brand（okumanchoja/kamakura）× store（shinjuku/kinshicho）の2次元に分離し、
        初期pushと全イベントに brand/store を付与する
     3) review_badge_click は gRev v2.0 バッジ本体(.gb-chip)の直接タップも捕捉し、
        source（badge/header/drawer）で入口を区別する。#hdCta/#dwGr → .gb-chip への
        合成クリック(isTrusted=false)は除外し二重計上を防ぐ
   読み込み位置：各ページ<head>内・GTMスニペットより前（初期pushをGTM読込前に積むため）
   排他ルール：予約リンク=cta_clickのみ／#hdCta・#dwGr・.gb-chip=review_badge_clickのみ（outbound_clickは出さない）
   LP本体への影響なし（全処理try/catch。GTM未設置でもdataLayerは積まれる） */
(function(){
  try{
    window.dataLayer = window.dataLayer || [];
    function push(o){ try{ window.dataLayer.push(o); }catch(e){} }

    var BRAND="okumanchoja", STORE="shinjuku";
    var file=(location.pathname.split("/").pop()||"").toLowerCase();
    var PAGE_TYPE = file.indexOf("menu")===0 ? "menu" : file.indexOf("course")===0 ? "course" : "top";

    /* --- 初期push（ページ属性。GTM側はデータレイヤー変数 brand / store / page_type で参照） --- */
    push({brand:BRAND, store:STORE, page_type:PAGE_TYPE});

    function base(o){ o.brand=BRAND; o.store=STORE; return o; }
    function label(a){ return (a.textContent||"").replace(/\s+/g," ").trim().slice(0,40); }

    /* --- クリック系（委譲・キャプチャ段階で確実に捕捉。ページ側のpreventDefaultより先に動く） --- */
    document.addEventListener("click",function(e){
      try{
        var t=e.target; if(!t||!t.closest) return;
        /* gRev バッジ本体（div.gb-chip）。合成クリック(isTrusted=false)はヘッダー/ドロワー経由のリレーなので除外 */
        var chip=t.closest(".gb-chip");
        if(chip){
          if(e.isTrusted!==false){ push(base({event:"review_badge_click", source:"badge"})); }
          return;
        }
        var a=t.closest("a[href]");
        if(!a) return;
        var id=a.id||"";
        var h=a.getAttribute("href")||"";
        if(id==="hdCta"||id==="dwGr"){
          /* Googleレビュー導線（ポップアップ／Maps直リンクの両モード共通） */
          push(base({event:"review_badge_click", source:(id==="hdCta"?"header":"drawer")}));
        }else if(h.indexOf("tel:")===0 || a.hasAttribute("data-tel")){
          push(base({event:"tel_click", tel_number:h.replace("tel:","")}));
        }else if(h.indexOf("booking.ebica.jp")>=0 || a.hasAttribute("data-ebica")){
          push(base({event:"cta_click", cta_type:"reserve", cta_label:label(a)}));
        }else if(h.indexOf("google.com/maps")>=0 || h.indexOf("maps.app.goo.gl")>=0){
          push(base({event:"outbound_click", link_type:"map", url:h}));
        }else if(h.indexOf("instagram.com")>=0 || a.hasAttribute("data-insta")){
          push(base({event:"outbound_click", link_type:"sns", url:h}));
        }
      }catch(err){}
    }, true);

    /* --- セクション到達（1回のみ・KAMAKURAと同じ位置判定方式：上端が画面高85%より上に入ったら到達） --- */
    var MARKS={
      top:    [{sel:"#concept",id:"concept"},{sel:"#menu",id:"menu"},{sel:"#sns",id:"sns"},{sel:"#access",id:"access"}],
      menu:   [{sel:"#shwSp",id:"shwSp"},{sel:"#shwWg",id:"shwWg"},{sel:"section.fin",id:"fin"}],
      course: [{sel:"section.crs",id:"crs"},{sel:"section.fin",id:"fin"}]
    };
    var marks=MARKS[PAGE_TYPE]||[];
    function chk(){
      try{
        for(var i=0;i<marks.length;i++){
          var m=marks[i];
          if(m.done) continue;
          var el=document.querySelector(m.sel);
          if(!el) continue;
          var r=el.getBoundingClientRect();
          if(r.top < window.innerHeight*0.85 && r.bottom > 0){
            m.done=true;
            push(base({event:"scroll_reach", section_id:m.id}));
          }
        }
      }catch(e){}
    }
    window.addEventListener("scroll",chk,{passive:true});
    if(document.readyState==="loading"){ document.addEventListener("DOMContentLoaded",chk); } else { chk(); }
    setInterval(chk,1000);
  }catch(e){}
})();
