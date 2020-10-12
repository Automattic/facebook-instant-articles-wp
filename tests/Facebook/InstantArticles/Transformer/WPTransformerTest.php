<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Transformer;

use Facebook\InstantArticles\Transformer\Transformer;
use Facebook\InstantArticles\Elements\InstantArticle;
use Facebook\InstantArticles\Elements\Header;
use Facebook\InstantArticles\Elements\Time;
use Facebook\InstantArticles\Elements\Author;

class WPTransformerTest extends \PHPUnit_Framework_TestCase {

	public function testTransformerLikeWPContent() {
		$json_file = file_get_contents( __DIR__ . '/wp-rules.json' );

		$instant_article = InstantArticle::create();
		$transformer     = new Transformer();
		$transformer->loadRules( $json_file );

		$html_file = file_get_contents( __DIR__ . '/wp.html' );

		libxml_use_internal_errors( true );
		$document = new \DOMDocument();
		$document->loadHTML( $html_file );
		libxml_use_internal_errors( false );

		$instant_article
			->withCanonicalURL( 'http://localhost/article' )
			->withHeader(
				Header::create()
					->withTitle( 'Peace on <b>earth</b>' )
					->addAuthor( Author::create()->withName( 'bill' ) )
					->withPublishTime(
						Time::create( Time::PUBLISHED )->withDatetime(
							\DateTime::createFromFormat(
								'j-M-Y G:i:s',
								'12-Apr-2016 19:46:51'
							)
						)
					)
			);

		$transformer->transform( $instant_article, $document );
		$instant_article->addMetaProperty( 'op:generator:version', '1.0.0' );
		$instant_article->addMetaProperty( 'op:generator:transformer:version', '1.0.0' );
		$result   = $instant_article->render( '', true ) . "\n";
		$expected = file_get_contents( __DIR__ . '/wp-ia.xml' );

		$this->assertEquals( $expected, $result );
		// there must be 3 warnings related to <img> inside <li> that is not supported by IA
		$this->assertEquals( 3, count( $transformer->getWarnings() ) );
	}

	public function testTitleTransformedWithBold() {
		$transformer = new Transformer();
		$json_file   = file_get_contents( __DIR__ . '/wp-rules.json' );
		$transformer->loadRules( $json_file );

		$title_html_string = '<?xml encoding="utf-8" ?><h1>Title <b>in bold</b></h1>';

		libxml_use_internal_errors( true );
		$document = new \DOMDocument();
		$document->loadHtml( $title_html_string );
		libxml_use_internal_errors( false );

		$header = Header::create();
		$transformer->transform( $header, $document );

		$this->assertEquals( '<h1>Title <b>in bold</b></h1>', $header->getTitle()->render() );
	}
}
