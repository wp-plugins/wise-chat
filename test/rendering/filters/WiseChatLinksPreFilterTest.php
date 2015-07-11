<?php

require_once('src/rendering/filters/WiseChatLinksPreFilter.php');
require_once('src/rendering/filters/WiseChatShortcodeConstructor.php');
require_once('src/messages/WiseChatImagesDownloader.php');

class WiseChatLinksPreFilterTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * @dataProvider dataNoImages
	 */
    public function testPositiveNoImages($input, $output) {
		$linksPreFilter = new WiseChatLinksPreFilter(new WiseChatImagesDownloaderStub());
		
		$this->assertEquals($output, $linksPreFilter->filter($input, 'channel', false));
    }
    
    public function dataNoImages() {
		return array(
			array("", ''),
			array("no links.", 'no links.'),
			array("no links &#^$%%.54$>$#.4545", 'no links &#^$%%.54$>$#.4545'),
			array("test1 wp.pl test2", 'test1 [link src="wp.pl"] test2'),
			array("test1 wp.pl?oo=p test2", 'test1 [link src="wp.pl?oo=p"] test2'),
			array("test1 wp.pl?oo=p%27uop test2", 'test1 [link src="wp.pl?oo=p%27uop"] test2'),
			array("test1 wp.pl?oo=p%3Cuop test2", 'test1 [link src="wp.pl?oo=p%3Cuop"] test2'),
			array("test1 http://wp.pl test2", 'test1 [link src="http://wp.pl"] test2'),
			array("ftp://wp.pl", '[link src="ftp://wp.pl"]'),
			array("test1 http://wp.pl https://sdads.test2.pl", 'test1 [link src="http://wp.pl"] [link src="https://sdads.test2.pl"]'),
			array("test1 https://wp.pl/oo/ddd/r.html test2", 'test1 [link src="https://wp.pl/oo/ddd/r.html"] test2'),
			array("test1 https://wp.pl/oo/ddd/r.html?oo=ww test2", 'test1 [link src="https://wp.pl/oo/ddd/r.html?oo=ww"] test2'),
			array("test1 https://wp.pl/oo/ddd/r.html?oo=ww][ test2", 'test1 [link src="https://wp.pl/oo/ddd/r.html?oo=ww]["] test2'),
			array("test1 http://wp.pl?oop=sss&eee=qqq+333 test2", 'test1 [link src="http://wp.pl?oop=sss&eee=qqq+333"] test2'),
			array("test1 http://wp.pl?oop=sss&eee=q&lt;qq+333 test2", 'test1 [link src="http://wp.pl?oop=sss&eee=q&lt;qq+333"] test2'),
			array("test1 wp.pl onet.pl test2", 'test1 [link src="wp.pl"] [link src="onet.pl"] test2'),
			array("test1 wp.pl?op=ii'kkj onet.pl yxy wp.pl test2", 'test1 [link src="wp.pl?op=ii"]\'kkj [link src="onet.pl"] yxy [link src="wp.pl"] test2'),
			array("test1 wp.pl onet.pl yxy wp.pl uu http://onet.pl", 'test1 [link src="wp.pl"] [link src="onet.pl"] yxy [link src="wp.pl"] uu [link src="http://onet.pl"]'),
			array("hhhh.oooo.pl  dcdsc hhhh.oooo.pl http://hhhh.oooo.pl", '[link src="hhhh.oooo.pl"]  dcdsc [link src="hhhh.oooo.pl"] [link src="http://hhhh.oooo.pl"]'),
			array("https://www.google.pl/webhp?hl=pl#hl=pl&q=pozna%C5%84+%22:)%22 - the link", '[link src="https://www.google.pl/webhp?hl=pl#hl=pl&q=pozna%C5%84+%22:)%22"] - the link'),
			
			// special cases:
			array("http://wp.pl?ss=the\"rest", '[link src="http://wp.pl?ss=the"]"rest'),
			array("http://wp.pl?ss=pozna%C5%84+%22:)%22'rest", '[link src="http://wp.pl?ss=pozna%C5%84+%22:)%22"]\'rest'),
			array("wp.pl?ss=pozna%C5%84+%22:)%22'rest", '[link src="wp.pl?ss=pozna%C5%84+%22:)%22"]\'rest'),
		);
    }
    
    /**
	 * @dataProvider dataWithImages
	 */
    public function testPositiveWithImages($input, $output) {
		$linksPreFilter = new WiseChatLinksPreFilter(new WiseChatImagesDownloaderStub());
		
		$this->assertEquals($output, $linksPreFilter->filter($input, 'channel', true));
    }
    
    public function dataWithImages() {
		return array(
			array("", ''),
			array("no links.", 'no links.'),
			array("no links &#^$%%.54$>$#.4545", 'no links &#^$%%.54$>$#.4545'),
			array("the.image.pl/my.jpg", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.jpg"]'),
			array("the.image.pl/my.gif", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.gif"]'),
			array("the.image.pl/my.png", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.png"]'),
			array("the.image.pl/my.bmp", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.bmp"]'),
			array("the.image.pl/my.tiff", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.tiff"]'),
			array("the.image.pl/my.tiff?a=s&s", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.tiff?a=s&s"]'),
			array("http://the.image.pl/my.tiff", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="http://the.image.pl/my.tiff"]'),
			array("[http://the.image.pl/my.tiff]", '[[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="http://the.image.pl/my.tiff]"]'),
			array("H: http://the.image.pl/my.tiff", 'H: [img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="http://the.image.pl/my.tiff"]'),
			array("H: http://the.image.pl/my.tiff?hl=pl&q=pozna%C5%84+%22:)%22", 'H: [img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="http://the.image.pl/my.tiff?hl=pl&q=pozna%C5%84+%22:)%22"]'),
			array("the.image.pl/my.jpg?thecode=3445324", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.jpg?thecode=3445324"]'),
			array("test1 wp.pl the.image.pl/my.jpg test2", 'test1 [link src="wp.pl"] [img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.jpg"] test2'),
			array("test1 http://wp.pl the.image.pl/my.jpg test2", 'test1 [link src="http://wp.pl"] [img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="the.image.pl/my.jpg"] test2'),
			
			array("image.pl/my.jpg http://image.pl/my.jpg image2.pl/my2.jpg wp.pl", '[img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="image.pl/my.jpg"] [img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="http://image.pl/my.jpg"] [img id="1" src="IMAGE_SRC" src-th="IMAGE_TH_SRC" src-org="image2.pl/my2.jpg"] [link src="wp.pl"]'),
		);
    }
}

class WiseChatImagesDownloaderStub extends WiseChatImagesDownloader {
	public function __construct() { }
	
	public function downloadImage($imageUrl, $channel) {
		return array(
			'id' => 1,
			'image' => 'IMAGE_SRC',
			'image-th' => 'IMAGE_TH_SRC'
		);
	}
}