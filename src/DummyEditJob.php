<?php

namespace SDU;

use CommentStoreComment;
use ContentHandler;
use Job;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MWContentSerializationException;
use MWException;
use Title;
use User;
use WikiPage;

class DummyEditJob extends Job {

	/**
	 * @param Title $title
	 */
	public function __construct( $title ) {
		parent::__construct( 'DummyEditJob', $title );
	}

	/**
	 * Run the job
	 *
	 * @return bool success
	 * @throws MWContentSerializationException
	 * @throws MWException
	 */
	public function run() {
		$page = WikiPage::newFromID( $this->getTitle()->getArticleId() );
		if ( $page ) {
			// prevent NPE when page not found
			$content = $page->getContent( RevisionRecord::RAW );
			if ( $content ) {
				$text = ContentHandler::getContentText( $content );
				$updater = $page->newPageUpdater( User::newSystemUser( 'Semantic Dependency Updater' ) );
				$updater->setContent(
					SlotRecord::MAIN,
					ContentHandler::makeContent(
						$text,
						$this->getTitle()
					)
				);
				$summary = CommentStoreComment::newUnsavedComment( "[SemanticDependencyUpdater] Null edit." );
				$updater->saveRevision( $summary );
				// since this is a null edit, the edit summary will be ignored.
				// required since SMW 2.5.1
				$page->doPurge();

				# Consider calling doSecondaryDataUpdates() for MW 1.32+
				# https://doc.wikimedia.org/mediawiki-core/master/php/classWikiPage.html#ac761e927ec2e7d95c9bb48aac60ff7c8
			}
		}

		return true;
	}
}
