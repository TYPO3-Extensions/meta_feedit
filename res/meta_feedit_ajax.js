var mdown=false;
var mfedtcalcat=undefined;
jQuery.noConflict();
AC_FL_RunContent = 0;  
DetectFlashVer = 0;
var requiredMajorVersion = 8;
var requiredMinorVersion = 0;
var requiredRevision = 0;

function metaFeeditLoadHourGlass() {
		 jQuery("#ardhourglass").remove();
		 jQuery("#ardoverlay").remove();
		 jQuery("body").append('<div id="ardhourglass">&nbsp;</div>');
		 jQuery("body").append('<div id="ardoverlay">&nbsp;</div>');
		 jQuery("#ardoverlay").height(jQuery(document).height());
		 jQuery("#ardhourglass").css('top',jQuery(document).scrollTop());
		 jQuery("#ardoverlay").width(jQuery(document).width());
		 jQuery("#ardhourglass").click(function () {metaFeeditUnloadHourGlass();});
		 jQuery("#ardoverlay").click(function () {metaFeeditUnloadHourGlass();});
}

function metaFeeditUnloadHourGlass() {
 jQuery("#ardhourglass").remove();
 jQuery("#ardoverlay").remove();
 //jQuery("body").click(function() {alert('u');});
} 

function cbywebcamUpdateImage(id,fileName,thumb) {
	jQuery('#sel_'+id).append('<option value="'+fileName+'">'+fileName+'</option>');
	var val=jQuery('#selh_'+id).attr('value');
	val=val?val+','+fileName:fileName;
	jQuery('#selh_'+id).attr('value',val);
	jQuery('#flash_'+id).after(thumb);
	jQuery('#flash_'+id).jqmHide();
}
jQuery().ready(function() {
	initEdtBtns();
	initDelBtns();
	jQuery('a').prepend('<i>&nbsp;</i>');
	jQuery('button').prepend('<i>&nbsp;</i>');
	//jQuery('#modalWindow').jqm({modal: true,trigger: '.tx-metafeedit-list_field_image a',target: '#jqmContent',onHide: closeModDel,onShow: openInFrame}).jqDrag('.jqDrag').jqResize('.jqResize');
	jQuery('input.tx-metafeedit-form-submit').bind('click',function() {
		jQuery.each(jQuery('.tx-metafeedit-form-data-starttime'),function() {this.onchange()});
		jQuery.each(jQuery('.tx-metafeedit-form-data-endtime'),function() {this.onchange()});
	});
	// only in calendar mode !
	initCal();
	jQuery().mousedown(mDown);
	jQuery().mouseup(mUp);
});
var initCal = function() {
	jQuery.each(jQuery('div.tx-metafeedit-cal-hour-1'),function () {
	jQuery(this).bind('mouseover',mClick);
	UnSelectable(this);
	jQuery(this).bind('mousedown',mClick2)});
	jQuery.each(jQuery('div.tx-metafeedit-cal-hour-2'),function () {
	jQuery(this).bind('mouseover',mClick);
	UnSelectable(this);
	jQuery(this).bind('mousedown',mClick2)});
	jQuery.each(jQuery('div.tx-metafeedit-cal-hourlib-2'),function () {UnSelectable(this);});
	jQuery.each(jQuery('div.tx-metafeedit-cal-hourlib-1'),function () {UnSelectable(this);});
	jQuery.each(jQuery('div.cal-day-title'),function () {UnSelectable(this);});
	mfedtcalcat=jQuery('#txmfedtcalcat-1');
	jQuery(mfedtcalcat).addClass("mfedtCatSelected");
	jQuery.each(jQuery('.txmfedtccat'),function () {initCalCat(this);});
	
	jQuery.each(jQuery('.tx_mfedt_ft_img'), function () {
		id=jQuery(this).attr('id');
		var tab=id.split('$');
		var flashvars='table='+tab[0]+'&imageField='+tab[1]+'&uid='+tab[2];
	    var	flashid=tab[0]+'_'+tab[1]+'_'+tab[2];
		var flash='none';
		jQuery(this).after('<button id="img_'+flashid+'" href="#" class="cbywcm"><i>&nbsp;</i>Webcam</button><div id="flash_'+flashid+'" class="jqmWindow"><div id="jqmTitle" class="jqmTitle jqDrag"><button class="jqmClose"><i>&nbsp;</i>X</button><span id="jqmTitleText" class="jqmTitleText">Webcam</span></div><div class="jqmContent"></div></div>');
		jQuery('#img_'+flashid).bind('click', {id:flashid,fv:flashvars}, function(e){
			
			if (AC_FL_RunContent == 0 || DetectFlashVer == 0) {
				alert("This page requires the script AC_RunActiveContent.js found in extension  cby_webcam");
			} else {
				var hasRightVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);
				if(hasRightVersion) {
     			flash=AC_FL_RunContent(
					'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0',
					'width', '360',
					'height', '180',
					'src', 'typo3conf/ext/cby_webcam/res/cbywebcam',
					'quality', 'high',
					'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
					'align', 'middle',
					'play', 'true',
					'FlashVars',e.data.fv,
					'loop', 'true',
					'scale', 'showall',
					'wmode', 'window',
					'devicefont', 'false',
					'id', 'cbywebcam_'+e.data.id,
					'bgcolor', '#FFFFFF',
					'name', 'cbywebcam_'+e.data.id,
					'menu', 'true',
					'allowScriptAccess','sameDomain',
					'allowFullScreen','false',
					'movie', 'typo3conf/ext/cby_webcam/res/cbywebcam',
					'salign', ''
					);
				} else {  

					var alternateContent = 'Another html content must be inserted here '
					+ 'This content requires Adobe Flash Player. '
					+ '<a href=http://www.macromedia.com/go/getflash/>Get Flash</a>';
					flash=alternateContent;  

				}			
			}
     	jQuery('#flash_'+e.data.id+ ' .jqmContent').html(flash);
     	jQuery('#flash_'+e.data.id).jqm({modal: true,trigger: '.jqMFModal',target: '#jqmContent'}).jqDrag('.jqDrag').jqResize('.jqResize').jqmShow();
  		return(false);
  	});
			
	});
	jQuery.each(jQuery('ul.astabnav li a'),function () {
		jQuery(this).bind('click',function() {
				id=jQuery(this).attr('id');
				jQuery('ul.astabnav li').removeClass("active");
				jQuery(this).parent().addClass("active");
				jQuery('div.tx-metafeedit-as').hide();
				id="#"+id.substr(0,id.length-2);
				jQuery(id).show();
			});
		});
}

var mDown = function() {
	mdown=true;
}
var initDelBtns = function() {
	jQuery('#modalDelWindow').jqm({modal: true,trigger: '.jqMFDelModal',target: '#jqmDelContent',onHide: closeModDel,onShow: openInFrameAjx}).jqDrag('.jqDrag').jqResize('.jqResize');
}
var initEdtBtns = function() {
	jQuery('#modalWindow').jqm({modal: true,trigger: '.jqMFModal',target: '#jqmContent',onHide: closeModal,onShow: openInFrame}).jqDrag('.jqDrag').jqResize('.jqResize');
	jQuery('#modalImgWindow').jqm({modal: true,trigger: '.tx-metafeedit-list_field_image a',target: '#jqmImgContent',onHide: closeModal,onShow: showImage});
}
var mUp = function() {
	mdown=false;
}

var initCalCat = function(obj) {
	jQuery(obj).click(selectCalCat);
}

var selectCalCat = function() {
	jQuery(mfedtcalcat).removeClass("mfedtCatSelected");
	mfedtcalcat=this;
	jQuery(mfedtcalcat).addClass("mfedtCatSelected");
}

var UnSelectable = function(obj) {
    if (jQuery.browser.mozilla) {
       jQuery(obj).each(function() {
                jQuery(this).css({
                    'MozUserSelect' : 'none'
                });
            });
    } else if (jQuery.browser.msie) {
        jQuery(obj).each(function() {
                jQuery(this).bind('selectstart', function() {
                    return false;
                });
            });
    } else {
        jQuery(obj).each(function() {
                jQuery(this).mousedown(function() {
                    return false;
                });
            });
    }
}

// We set TimeSlot color without checking mouse botton (for click event for example)

var mClick2 = function() {
		jQuery(this).css({'background-color':jQuery(mfedtcalcat).css("background-color")});
}

// We set TimeSlot color if we are moving mouse over timeslot while pressing mouse button

var mClick = function() {
	if (mdown==true) {
		//jQuery(this).css({'background-color':'red'});
		jQuery(this).css({'background-color':jQuery(mfedtcalcat).css("background-color")});
	}
}

var closeMod = function(hash)
{
  var $modalWindow = jQuery(hash.w);
 	$modalWindow.fadeOut('2000', function()
  {
     hash.o.remove();
   });
 }
 
var closeModDel = function(hash)
{
  var $modalWindow = jQuery(hash.w);
 	$modalWindow.fadeOut('2000', function()
  {
     hash.o.remove();
 		 initEdtBtns();
 		 initDelBtns();
   });
 }
  
var closeModal = function(hash)
{
        var $modalWindow = jQuery(hash.w);

         $modalWindow.fadeOut('2000', function()
        {
            hash.o.remove();
            //refresh parent

            if (hash.refreshAfterClose == true)
            {
                window.location.href = document.location.href+((document.location.href.lastIndexOf('cmd=list&ajxcb=1') > -1)?'':((document.location.href.lastIndexOf('?') > -1)?'&cmd=list&ajxcb=1':'?&cmd=list&ajxcb=1'));
				//alert(window.location.href);
            }
            initEdtBtns();
		 	initDelBtns();

        });
    };

var refreshModal = function(hash)
{
    var $modalWindow = jQuery(hash.w);

            if (hash.refreshAfterClose == true)
            {
                window.location.href = document.location.href;
            }
};
   var showImage = function(hash)
    {
    		if (hash.t==undefined) return false;
        var $trigger = jQuery(hash.t);
        var $modalWindow = jQuery(hash.w);
        var $modalContainer = jQuery('#jqmImgContent', $modalWindow);
        var myUrl = $trigger.attr('href');
        $modalContainer.html('<img src="'+myUrl+'"/>');
        //$modalWindow.jqmShow();
        hash.w.show(); 
    }
    var openInFrame = function(hash)
    {
    	if (hash.t==undefined) return false;
        var $trigger = jQuery(hash.t);
        var $modalWindow = jQuery(hash.w);
        var $modalContainer = jQuery('iframe', $modalWindow);

        var myUrl = $trigger.attr('href')+'&jqmRefresh=true';
        var myTitle = $trigger.attr('title');
        var newWidth = 0, newHeight = 0, newLeft = 0, newTop = 0;
        myUrl=(myUrl.lastIndexOf(".0.html") > -1) ? myUrl.replace(/.0.html/,'.9002.html') : myUrl;
        $modalContainer.html('').attr('src', myUrl); // CBY typenum  to be put in config
        jQuery('#jqmTitleText').text(myTitle);
      	myUrl = (myUrl.lastIndexOf("#") > -1) ? myUrl.slice(0, myUrl.lastIndexOf("#")) : myUrl;
        var queryString = (myUrl.indexOf("?") > -1) ? myUrl.substr(myUrl.indexOf("?") + 1) : null;
        if (queryString != null && typeof queryString != 'undefined')
        {
            var queryVarsArray = queryString.split("&");
            for (var i = 0; i < queryVarsArray.length; i++)
            {
                if (unescape(queryVarsArray[i].split("=")[0]) == 'width')
                {
                    var newWidth = queryVarsArray[i].split("=")[1];
                }
                if (escape(unescape(queryVarsArray[i].split("=")[0])) == 'height')
                {
                    var newHeight = queryVarsArray[i].split("=")[1];
                }
                if (escape(unescape(queryVarsArray[i].split("=")[0])) == 'jqmRefresh')
                {
                    // if true, launches a "refresh parent window" order after the modal is closed.
                    hash.refreshAfterClose = eval(queryVarsArray[i].split("=")[1]);
                } else
                {

                    hash.refreshAfterClose = false;
                }
            }
            // let's run through all possible values: 90%, nothing or a value in pixel
            if (newHeight != 0)
            {
                if (newHeight.indexOf('%') > -1)
                {

                    newHeight = Math.floor(parseInt(jQuery(window).height()) * (parseInt(newHeight) / 100));

                }
            }
            else
            {
                newHeight = $modalWindow.height();
            }
			
            var newTop = Math.floor(parseInt(jQuery(window).height() - newHeight) / 2);
            if (newWidth != 0)
            {
                if (newWidth.indexOf('%') > -1)
                {
                    newWidth = Math.floor(parseInt(jQuery(window).width() / 100) * parseInt(newWidth));
                }
            }
            else
            {
                newWidth = $modalWindow.width();
            }
            var newLeft = Math.floor(parseInt(jQuery(window).width() / 2) - parseInt(newWidth) / 2);
			if (newTop <0) {
				newHeight=jQuery(window).height()-50;
				newTop=10;
			}
            // do the animation so that the windows stays on center of screen despite resizing
            $modalWindow.css({
                width: newWidth,
                height: newHeight,
                opacity: 0
            }).jqmShow().animate({
                width: newWidth,
                height: newHeight,
                top: newTop,
                left: newLeft,
                marginLeft: 0,
                opacity: 1
            }, 'slow');
        }
        else
        {
            // don't do animations
            $modalWindow.jqmShow();
        }

    }
    var openInFrameImg = function(hash)
    {
    	if (hash.t==undefined) return false;
        var $trigger = jQuery(hash.t);
        var $modalWindow = jQuery(hash.w);
        var $modalContainer = jQuery('a img', $modalWindow);

        var myUrl = $trigger.attr('src');
        var myTitle = $trigger.attr('title');
        var newWidth = 0, newHeight = 0, newLeft = 0, newTop = 0;
        $modalContainer.html('').attr('src', myUrl); // CBY typenum  to be put in config
        jQuery('#jqmTitleText').text(myTitle);
      	myUrl = (myUrl.lastIndexOf("#") > -1) ? myUrl.slice(0, myUrl.lastIndexOf("#")) : myUrl;
        var queryString = (myUrl.indexOf("?") > -1) ? myUrl.substr(myUrl.indexOf("?") + 1) : null;
        if (queryString != null && typeof queryString != 'undefined')
        {
            var queryVarsArray = queryString.split("&");
            for (var i = 0; i < queryVarsArray.length; i++)
            {
                if (unescape(queryVarsArray[i].split("=")[0]) == 'width')
                {
                    var newWidth = queryVarsArray[i].split("=")[1];
                }
                if (escape(unescape(queryVarsArray[i].split("=")[0])) == 'height')
                {
                    var newHeight = queryVarsArray[i].split("=")[1];
                }
                if (escape(unescape(queryVarsArray[i].split("=")[0])) == 'jqmRefresh')
                {
                    // if true, launches a "refresh parent window" order after the modal is closed.
                    hash.refreshAfterClose = eval(queryVarsArray[i].split("=")[1]);
                } else
                {

                    hash.refreshAfterClose = false;
                }
            }
            // let's run through all possible values: 90%, nothing or a value in pixel
            if (newHeight != 0)
            {
                if (newHeight.indexOf('%') > -1)
                {

                    newHeight = Math.floor(parseInt(jQuery(window).height()) * (parseInt(newHeight) / 100));

                }
            }
            else
            {
                newHeight = $modalWindow.height();
            }
            var newTop = Math.floor(parseInt(jQuery(window).height() - newHeight) / 2);
            if (newWidth != 0)
            {
                if (newWidth.indexOf('%') > -1)
                {
                    newWidth = Math.floor(parseInt(jQuery(window).width() / 100) * parseInt(newWidth));
                }
            }
            else
            {
                newWidth = $modalWindow.width();
            }
            var newLeft = Math.floor(parseInt(jQuery(window).width() / 2) - parseInt(newWidth) / 2);
            // do the animation so that the windows stays on center of screen despite resizing
            $modalWindow.css({
                width: newWidth,
                height: newHeight,
                opacity: 0
            }).jqmShow().animate({
                width: newWidth,
                height: newHeight,
                top: newTop,
                left: newLeft,
                marginLeft: 0,
                opacity: 1
            }, 'slow');
        }
        else
        {
            // don't do animations
            $modalWindow.jqmShow();
        }

    }
    var openInFrameAjx = function(hash)
    {
    	if (hash.t==undefined) return false;
        var $trigger = jQuery(hash.t);
        var $modalWindow = jQuery(hash.w);
        //var $modalContainer = jQuery('iframe', $modalWindow);

        var myUrl = $trigger.attr('href')+'&jqmRefresh=true';
        var myTitle = $trigger.attr('title');
        var newWidth = '50%', newHeight = '50%', newLeft = 0, newTop = 0;
        myUrl=(myUrl.lastIndexOf(".0.html") > -1) ? myUrl.replace(/.0.html/,'.9002.html') : myUrl;
        jQuery('#jqmDelContent').load(myUrl+'&ajx=1',{},function() {
			//jQuery('#jqmDelContent').load(myUrl+'&ajx=1&eID=tx_metafeedit_pi1&type=9002',{},function() {
        	jQuery('.tx-metafeedit-link-delete-ok a').click(function() {jQuery.get(jQuery('.tx-metafeedit-link-delete-ok a').attr('href'));window.location.href = document.location.href;return false});
        	hash.w.jqmAddClose(jQuery('.tx-metafeedit-link-delete-ok a',hash.w));
        	hash.w.jqmAddClose(jQuery('.tx-metafeedit-link-delete-ko a',hash.w));
		 	//initDelBtns();
       	});
		
        jQuery('#jqmTitleText').text(myTitle);
      	myUrl = (myUrl.lastIndexOf("#") > -1) ? myUrl.slice(0, myUrl.lastIndexOf("#")) : myUrl;
        var queryString = (myUrl.indexOf("?") > -1) ? myUrl.substr(myUrl.indexOf("?") + 1) : null;
        if (queryString != null && typeof queryString != 'undefined')
        {
            var queryVarsArray = queryString.split("&");
            for (var i = 0; i < queryVarsArray.length; i++)
            {
                if (unescape(queryVarsArray[i].split("=")[0]) == 'width')
                {
                    var newWidth = queryVarsArray[i].split("=")[1];
                }
                if (escape(unescape(queryVarsArray[i].split("=")[0])) == 'height')
                {
                    var newHeight = queryVarsArray[i].split("=")[1];
                }
                if (escape(unescape(queryVarsArray[i].split("=")[0])) == 'jqmRefresh')
                {
                    // if true, launches a "refresh parent window" order after the modal is closed.
                    hash.refreshAfterClose = eval(queryVarsArray[i].split("=")[1]);
                } else
                {

                    hash.refreshAfterClose = false;
                }
            }
            // let's run through all possible values: 90%, nothing or a value in pixel
            if (newHeight != 0)
            {
                if (newHeight.indexOf('%') > -1)
                {

                    newHeight = Math.floor(parseInt(jQuery(window).height()) * (parseInt(newHeight) / 100));

                }
            }
            else
            {
                newHeight = $modalWindow.height();
            }
            var newTop = Math.floor(parseInt(jQuery(window).height() - newHeight) / 2);
            if (newWidth != 0)
            {
                if (newWidth.indexOf('%') > -1)
                {
                    newWidth = Math.floor(parseInt(jQuery(window).width() / 100) * parseInt(newWidth));
                }
            }
            else
            {
                newWidth = $modalWindow.width();
            }
            var newLeft = Math.floor(parseInt(jQuery(window).width() / 2) - parseInt(newWidth) / 2);
            // do the animation so that the windows stays on center of screen despite resizing
            $modalWindow.css({
                width: newWidth,
                height: newHeight,
                opacity: 0
            }).jqmShow().animate({
                width: newWidth,
                height: newHeight,
                top: newTop,
                left: newLeft,
                marginLeft: 0,
                opacity: 1
            }, 'slow');
        }
        else
        {
            // don't do animations
            $modalWindow.jqmShow();
        }

    }