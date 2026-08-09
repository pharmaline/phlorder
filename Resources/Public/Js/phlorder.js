/*
 * phlorder - Frontend-Helfer
 *
 * Aufraeumung in Phase 8 der v13-Migration. Entfernt wurden:
 *
 *  - test()          Debug-Funktion, rief die eID im alten Format auf
 *                    (index.php?eID=phlorderEID&_f=smocomp&...). Dieses Format
 *                    gibt es seit Phase 7 nicht mehr, der Endpunkt laeuft jetzt
 *                    ueber ?mw=phlorderEID. Die Funktion hatte keinen Aufrufer
 *                    (der einzige stand auskommentiert in document.ready).
 *  - getTestData()   Debug-Rest, baute ein Mail-Objekt und tat nichts damit.
 *  - controlSearch() rief die eID einer FREMDEN Extension auf
 *                    (eID: "phluserEID"). Auch dort ist das Format inzwischen
 *                    mw=phluserEID. Ohne Aufrufer, und das benoetigte Element
 *                    #searchbutton kommt in keinem phlorder-Template vor.
 *  - controlStatus() leerer Funktionsrumpf.
 *  - keyup-Handler auf #susers: rief analyseInput() und getDeliverybills() auf -
 *                    beide sind nirgends definiert (ReferenceError), und #susers
 *                    existiert in keinem phlorder-Template.
 *
 * Offen (siehe CLAUDE.md #18): Diese Datei haengt weiterhin an jQuery 2.2.0,
 * jquery.gritter und dem doTimeout-Plugin. Ob das mitgezogen oder wie in
 * phlaponot auf Vanilla JS umgestellt wird, ist noch nicht entschieden.
 */

/** main */
$(document).ready(function(){

	// Lade-Overlay waehrend laufender AJAX-Requests
	$(document)
	  .ajaxStart(function () {
		   $('.regajax-loader').css({
			    height: $(window).height()*1.5,
			}).css('cursor','progress');
			$('.regajax-loader').show();
		  })
	  .ajaxStop(function () {
			$('.regajax-loader').css('cursor','auto').hide();
	  });

});


/* Growler-Meldung (jquery.gritter). Aktuell ohne Aufrufer, aber als generischer
 * Helfer beibehalten - gritter wird ueber das Setup weiterhin geladen. */
function growler(title,text,image,sticky,time){
		if (typeof param == 'text') {
			text='';
		}
		if (typeof param == 'image') {
			image='';
		}
		if (typeof param == 'sticky') {
			sticky='false';
		}
		if (typeof param == 'time') {
			time='';
		}
		$.gritter.add({
				// (string | mandatory) the heading of the notification
				title: title,
				// (string | mandatory) the text inside the notification
				text: text,
				// (string | optional) the image to display on the left
				image: image,
				// (bool | optional) if you want it to fade out on its own or just sit there
				sticky: sticky,
				// (int | optional) the time you want it to be alive for before fading out
				time: time
			});
}


/*
 * jQuery doTimeout: Like setTimeout, but better! - v1.0 - 3/3/2010
 * http://benalman.com/projects/jquery-dotimeout-plugin/
 *
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function($){var a={},c="doTimeout",d=Array.prototype.slice;$[c]=function(){return b.apply(window,[0].concat(d.call(arguments)))};$.fn[c]=function(){var f=d.call(arguments),e=b.apply(this,[c+f[0]].concat(f));return typeof f[0]==="number"||typeof f[1]==="number"?this:e};function b(l){var m=this,h,k={},g=l?$.fn:$,n=arguments,i=4,f=n[1],j=n[2],p=n[3];if(typeof f!=="string"){i--;f=l=0;j=n[1];p=n[2]}if(l){h=m.eq(0);h.data(l,k=h.data(l)||{})}else{if(f){k=a[f]||(a[f]={})}}k.id&&clearTimeout(k.id);delete k.id;function e(){if(l){h.removeData(l)}else{if(f){delete a[f]}}}function o(){k.id=setTimeout(function(){k.fn()},j)}if(p){k.fn=function(q){if(typeof p==="string"){p=g[p]}p.apply(m,d.call(n,i))===true&&!q?o():e()};o()}else{if(k.fn){j===undefined?e():k.fn(j===false);return true}else{e()}}}})(jQuery);
