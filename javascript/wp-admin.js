var activeTab = null;

function showPage()
{
	var page = jQuery('#hiddenPages').find(':selected').val();
	if( page != '' && typeof( jQuery( '#' + page ) ) != 'undefined' )
	{
		jQuery( '#' + page ).fadeIn(function(){
      jQuery('html, body').animate({scrollTop: jQuery('#' + page).offset().top - jQuery('#wpadminbar').height() }, 'slow');
    });
	}
}

if( typeof(setCookie) === 'undefined' )
{
	function setCookie( name, value, days )
	{
		var expires;
		if( days )
		{
			var date = new Date();
			date.setTime( date.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
			expires = "; expires=" + date.toGMTString();
		}
		else
		{
			expires = "";
		}
		document.cookie = escape(name) + "=" + escape(value) + expires + "; path/";
	}
}

if( typeof(getCookie) === 'undefined' )
{
	function getCookie( name )
	{
		var nameEQ = escape(name) + "=";
		var ca = document.cookie.split(';');
		for( var i = 0; i < ca.length; i++ )
		{
			var c = ca[i];
			while( c.charAt(0) === ' ' ) c = c.substring( 1, c.length );
			if( c.indexOf(nameEQ) === 0 ) return unescape(c.substring(nameEQ.length,c.length));
		}
		return null;
	}
}

if( typeof(clrCookie) === 'undefined' )
{
	function clrCookie( name )
	{
		setCookie( name, "", -1 );
	}
}

function setActiveTab( tabID )
{
	jQuery('.tab-pane').each(function(){
		if( jQuery(this).attr('id') == tabID )
		{
			jQuery(this).show();
		}
		else
		{
			jQuery(this).hide();
		}
	});
	
	jQuery('.nav-tab-wrapper > a').each(function(){
		if( jQuery(this).attr('id') == tabID + '-nav' )
		{
			jQuery(this).addClass('nav-tab-active');
		}
		else
		{
			if( jQuery(this).hasClass('nav-tab-active') )
			{
				jQuery(this).removeClass('nav-tab-active');
			}
		}
	});
	
	setCookie( 'WPIS_DS_TAB', tabID, 1 );
}

jQuery(document).ready(function(){
	if( activeTab = getCookie('WPIS_DS_TAB') )
	{
		setActiveTab( activeTab );
	}
	if( activeTab == null )
	{
		setActiveTab('globalTab');
	}
});