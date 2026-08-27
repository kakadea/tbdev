<?php
/*
+------------------------------------------------
|   TBDev.net BitTorrent Tracker PHP
|   =============================================
|   by CoLdFuSiOn
|   (c) 2003 - 2011 TBDev.Net
|   http://www.tbdev.net
|   =============================================
|   svn: http://sourceforge.net/projects/tbdevnet/
|   Licence Info: GPL
+------------------------------------------------
|   $Date$
|   $Revision$
|   $Author$
|   $URL$
+------------------------------------------------
*/

  //-------- Begins a main frame

  function begin_main_frame()
  {
    return "<table class='main' width='739' border='0' cellspacing='0' cellpadding='0'>" .
      "<tr><td class='embedded'>\n";
  }

  //-------- Ends a main frame

  function end_main_frame()
  {
    return "</td></tr></table>\n";
  }

  function begin_frame($caption = "", $center = false, $padding = 0)
  {
    $tdextra = "";
    $htmlout = '';
    if ($caption)
      $htmlout .= "<div class='inner_header' style='text-align:left;'>$caption</div>\n";

    if ($center)
      $tdextra .= " align='center'";

    $htmlout .= "<table width='100%' border='1' cellspacing='0' cellpadding='$padding'><tr><td$tdextra>\n";

    return $htmlout;
  }

  function attach_frame($padding = 10)
  {
    print("</td></tr><tr><td style='border-top: 0px'>\n");
  }

  function end_frame()
  {
    return "</td></tr></table>\n";
  }

  function begin_table($fullwidth = false, $padding = 5)
  {
    $width = "";
    $htmlout = '';
    
    if ($fullwidth)
      $width .= " width='100%'";
    $htmlout .= "<table class='main' $width border='1' cellspacing='0' cellpadding='$padding'>\n";
    
    return $htmlout;
  }

  function end_table()
  {
    return "</table>\n";
  }
  
  //  function end_table()
//  {
//    print("</td></tr></table>\n");
//  }
  
	function tr($x,$y,$noesc=0) {
		if ($noesc)
			$a = $y;
		else {
			$a = htmlsafechars($y);
			$a = str_replace("\n", "<br />\n", $a);
		}
		
		return "<tr><td class='heading' valign='top' align='right'>$x</td><td valign='top' align='left'>$a</td></tr>\n";
	}


  //-------- Inserts a smilies frame

function insert_smilies_frame()
  {
    global $smilies, $TBDEV;
    
    $htmlout = '';
    
    $htmlout .= begin_frame("Smilies", true);

    $htmlout .= begin_table(false, 5);

    $htmlout .= "<tr><td class='colhead'>Type...</td><td class='colhead'>To make a...</td></tr>\n";

    foreach($smilies as $code => $url)
    {
      $htmlout .= "<tr><td>$code</td><td><img src=\"{$TBDEV['pic_base_url']}smilies/{$url}\" alt='' /></td></tr>\n";
    }
    
    $htmlout .= end_table();

    $htmlout .= end_frame();
    
    return $htmlout;
}


function bbcode2textarea($name = 'body', $body = '') {
    global $TBDEV;

    $safe_name = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $name);
    if ($safe_name === '')
        $safe_name = 'body';
    $body = htmlsafechars($body);
    $emot_dir = htmlsafechars($TBDEV['pic_base_url'] . 'smilies/');
    $editor_id = 'editor-' . $safe_name;

    $buttons = array(
        array('B', '[b]', '[/b]', 'font-weight:700'),
        array('I', '[i]', '[/i]', 'font-style:italic'),
        array('U', '[u]', '[/u]', 'text-decoration:underline'),
        array('URL', '', '', ''),
        array('IMG', '', '', ''),
        array('Quote', '[quote]', '[/quote]', ''),
        array('Code', '[code]', '[/code]', ''),
    );

    $htmlout = "<div class='bbcode-editor' data-editor='{$editor_id}'>";
    $htmlout .= "<div class='editor-toolbar' role='toolbar' aria-label='Formatting tools'>";
    foreach ($buttons as $button) {
        $label = htmlsafechars($button[0]);
        $style = $button[3] !== '' ? " style='{$button[3]}'" : '';
        if ($button[0] === 'URL')
            $onclick = 'tag_url();';
        elseif ($button[0] === 'IMG')
            $onclick = 'tag_image();';
        else
            $onclick = "addText('{$safe_name}', '{$button[1]}', '{$button[2]}');";
        $htmlout .= "<button type='button' class='editor-button'{$style} onclick=\"{$onclick}\">{$label}</button>";
    }
    $htmlout .= "<button type='button' class='editor-button' onclick=\"tag_list();\">List</button>";
    $htmlout .= "</div>";
    $htmlout .= "<textarea id='{$editor_id}' name='{$safe_name}' rows='12' class='editor-textarea'>{$body}</textarea>";
    $htmlout .= "<p class='field-help'>Use [img]https://...[/img] for an external image. Only HTTP(S) image URLs are rendered; HTML is never accepted.</p>";
    $htmlout .= "<div class='editor-smilies' aria-label='Emoticons'>";
    $smilies = array(
        array('smile1.gif', ':-)'), array('wink.gif', ':wink:'), array('noexpression.gif', ':-|'),
        array('sad.gif', ':-('), array('ohmy.gif', ':-O'), array('tongue.gif', ':-P'),
        array('cool2.gif', ':cool:'), array('grin.gif', ':-D'), array('angry.gif', ':angry:'),
        array('wub.gif', ':wub:'),
    );
    foreach ($smilies as $smiley) {
        $src = $emot_dir . htmlsafechars($smiley[0]);
        $code = htmlsafechars($smiley[1]);
        $onclick = "insertText('{$safe_name}', ' {$code}');";
        $htmlout .= "<button type='button' class='smiley-button' onclick=\"{$onclick}\"><img src='{$src}' alt='{$code}' loading='lazy' /></button>";
    }
    $htmlout .= "<button type='button' class='editor-button editor-more' onclick=\"more_emoticons();\">More emoticons</button>";
    $htmlout .= "</div></div>";
    return $htmlout;
}

?>