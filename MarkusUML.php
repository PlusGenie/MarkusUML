<?php
/**
 * Copyright (C) 2013 Sangwook Lee
 * Copyright (C) 2010 Arnoud Roques
 * Copyright (C) 2011 Pieter J. Kersten
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
* Summary
* Quick Sequence Diagram Editor is a tool for creating UML sequence diagrams
* from textual descriptions of objects and messages that follow a very easy syntax.
* ,which was made by Markus Strauch
*/

/*
 * Credit
 * This file referred to Mediawiki extension: PlantUML by Roques A., Kersten Pieter J.
 * For details, please check out http://www.mediawiki.org/wiki/Extension:PlantUML
 */

$version = "0.1";

$sdeditJar = 'sdedit-4.01.jar';

/* Quick Sequence Diagram Editor (QSDE)
 * Only png type was tested, QSDE supports PDF< SVG, GIF, JPE
 */
$QSDEImagetype = 'png';


/**
 * You can change the result of the getUploadDirectory() and getUploadPath()
 * if you want to put generated images somewhere else.
 * By default, it equals the upload directory. Mind that the process creating
 * the images must be able to create new files there.
 */
function getUploadDirectory() {
    global $wgUploadDirectory;
    return $wgUploadDirectory;
}

function getUploadPath() {
    global $wgUploadPath;
    return $wgUploadPath;
}

/*****************************************************************************
 * Don't change from here, unless you know what you're doing. If you do,
 * please consider sharing your changes and motivation with us.
 */

// Make sure we are being called properly
if( !defined( 'MEDIAWIKI' ) ) {
    echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
    die( -1 );
}

if (!is_file($sdeditJar)) {
	$sdeditJar = dirname(__FILE__).'/'.$sdeditJar;
	if (!is_file($sdeditJar))
	exit("Don't panic! let me just know that I am unable to open jar file $sdeditJar");
}

// Avoid unstubbing $wgParser too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
    $wgHooks['ParserFirstCallInit'][] = 'wfPlantUMLExtension';
    $wgHooks['ArticleSave'][] = 'cleanImages';
} else {
    $wgExtensionFunctions[] = 'wfPlantUMLExtension';
}

// Install extension
$wgExtensionCredits['parserhook'][] = array(
    'name' => 'UML',
    'version' => $version,
    'author' => 'Sangwook Lee',
    'url' => 'http://sdedit.sourceforge.net',
    'description' => 'Renders a UML model from text using Quick Sequence Diagram Editor.'
);

/**
 * Clean the image folder when required
 */
function cleanImages($parser=null) {
    $title_hash = md5(getPageTitle($parser));
    $path = getUploadDirectory()."/uml-".$title_hash."-*.{svg,png,cmapx}";
    $files = glob($path, GLOB_BRACE);
    foreach ($files as $filename) {
        unlink($filename);
    }
    return true;
}

/**
 * Register this extension with the WikiText parser.
 * The first parameter is the name of the new tag. In this case the
 * tag <uml> ... </uml>. The second parameter is the callback function
 * for processing the text between the tags.
 */
function wfPlantUMLExtension($parser) {
	$parser->setHook( 'uml', 'renderUML' );
	return true;
}

/**
 * Renders a PlantUML model by the using the following method:
 *  - write the formula into a wrapped plantuml file
 *  - Use a filename a md5 hash of the uml source
 *  - Launch PlantUML to create the PNG file into the picture cache directory
 *
 * @param string PlantUML_Source
 * @param string imgFile: full path of to-be-generated image file.
 * @param string dirname: directory of generated files
 * @param string filename_prefix: unique prefix for $dirname
 *
 * @returns the full path location of the rendered picture when
 *          successfull, false otherwise
 */
function callCmdLine($data, $imgFile, $dirname, $filename_prefix) {
    global $sdeditJar, $QSDEImagetype;

    // create temporary uml text file
    $umlFile = $dirname."/".$filename_prefix.".sml";
    $fp = fopen($umlFile,"w+");
    $w = fputs($fp,$data);
    fclose($fp);

    // Lauch PlantUML
    if ($QSDEImagetype == 'png') {
        $typestr = ' -t png';
    } else {
	die (-1);
    }

    $command = "java -jar ". $sdeditJar. "{$typestr}". ' '.  '-o'. "\"{$imgFile}\"". ' '. "\"{$umlFile}\"";

    /** Debugging
     * $status_code = system($command);
     */

    $status_code = exec($command);
    /* let us use system command to display error */

    // Delete temporary uml text file
    unlink($umlFile);

    // Only return existing path names.
    if (is_file($imgFile)) {
	return $imgFile;
    } else {
	return false;
   }
}

/**
 * Get a title for this page.
 * @returns title
 */
function getPageTitle($parser) {
    global $wgArticle;
    global $wgTitle;
    // Retrieving the title of a page is not that easy
    if (empty($wgTitle)) {
        $title = $parser->getTitle()->getFulltext();
        return $title;
    }
    return $wgTitle;
}

/**
 * Tries to match the PlantUML given as argument against the cache.
 * If the picture has not been rendered before, it'll
 * try to render the PlantUML and drop it in the picture cache directory.
 * Embedded links will be expanded into a image map file with the same
 * name, but extension ".cmapx". When found, it will be included in the
 * results.
 *
 * @param string model in been format
 * @returns an array with four elements:
 *   'src':   the webserver based URL to a picture which contains the
 *            requested PlantUML model. If anything fails, this value is
 *            false.
 *   'file':  the full pathname to the file containing the image map data
 *            when present. When no map data is present, this value is empty.
 *   'map':   the rendered HTML-fragment for an image map. Empty when not
 *            needed.
 *   'mapid': the unique id for the rendered image map , useable for further
 *            HTML-rendering.
 */
function getImage($PlantUML_Source, $argv, $parser=null) {
    global $QSDEImagetype;

    /** Debugging
    *global $wgOut;
    *$wgOut->addWikimsg( 'swlee: getImage ' );
    */

    // Compute hash
    $title_hash = md5(getPageTitle($parser));
    $formula_hash = md5($PlantUML_Source);

    $filename_prefix = 'uml-'.$title_hash."-".$formula_hash;
    $dirname = getUploadDirectory();
    $full_path_prefix = $dirname."/".$filename_prefix;

	// Prepare return value
    $result = array(
        'mapid' => $formula_hash, 'src' => false, 'map' => '', 'file' => ''
    );

    $imgFile = $dirname."/".$filename_prefix.".$QSDEImagetype";

    // Check cache. When found, reuse it. When not, generate image.
    // Honor the redraw tag as found in <uml redraw>
    if (is_file($imgFile) and !array_key_exists('redraw', $argv) ) {
        $result['file'] = $imgFile;
    } else {
        $result['file'] = callCmdLine($PlantUML_Source, $imgFile, $dirname, $filename_prefix);
    }

    if ($result['file']) {
        $result['src'] = getUploadPath()."/".basename($result['file']);
        if ($QSDEImagetype == 'png') {
            $map_filename = $full_path_prefix.".cmapx";
            if (is_file($map_filename)) {
                // map file is temporary data - read it and delete it.
                $fp = fopen($map_filename,'r');
                $image_map_data = fread($fp, filesize($map_filename));
                fclose($fp);
                //unlink($map_filename);
                // Replace generic ids with unique ids: first two ".." fields.
                $result['map'] = preg_replace('/"[^"]*"/', "\"{$result['mapid']}\"", $image_map_data, 2);
            }
        }
    } else {
//	$wgOut->addWikimsg( 'no image file from error');
    }

    return $result;
}

/**
 * renderPNG returns the correct HTML for the image in $image
 *
 * @param $image: the array of image data generated by getImage()
 * @returns: the rendered HTML string for the svg image.
 */
function renderPNG($image) {
    if ($image['map']) {
        $usemap = ' usemap="#'.$image['mapid'].'"';
    } else {
        $usemap = '';
    }

    return "<img class=\"marcusuml\" src=\"{$image['src']}\"$usemap>{$image['map']}";
}

/**
* @param String $data The input passed to <hook>
* @param Array $params The attributes of the <hook> element in array form
* @param Parser $parser Not used in this extension, but can be used to
* turn wikitext into html or do some other "advanced" stuff
* @param PPFrame $frame Not used in this extension, but can be used
* to see what template arguments ({{{1}}}) this hook was used with.
*
* @return String HTML to put in page at spot where <hook> tag is.
*/
function renderUML( $input, $argv, $parser=null ) {
    global $QSDEImagetype;

    $image = getImage($input, $argv, $parser);

    if ($image['src'] == false) {
        $text = "[MarkusUML.php: Probably Syntax error,
			please go back to the page and then check it ]";
    } else {
        if ($QSDEImagetype == 'png') {
		$text = renderPNG($image);
        } else {
		die (-1);
        }
    }
    return $text;
}
