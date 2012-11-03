<?php

class SpecialMobileDiff extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'MobileDiff' );
	}

	function execute( $par ) {
		$user = $this->getUser();
		$output = $this->getOutput();
		
		// @fixme validate
		$this->revId = intval( $par );
		$this->rev = Revision::newFromId( $this->revId );
		$this->targetTitle = $this->rev->getTitle();
		
		$output->setPageTitle( $this->msg( 'mobile-frontend-diffview-title', $this->targetTitle->getPrefixedText() ) );

		$output->addModules( 'mobile.watchlist' );

		$output->addHtml(
			Html::openElement( 'div', array( 'id' => 'mw-mf-diffview' ) )
		);

		$this->showHeader();
		$this->showDiff();
		$this->showFooter();

		$output->addHtml(
			Html::closeElement( 'div' )
		);
	}

	function showHeader() {
		$this->getOutput()->addHtml(
			Html::element( 'div', array( 'id' => 'mf-diff-comment' ), $this->rev->getComment() )
		);
	}

	function showDiff() {
		$prev = $this->rev->getPrevious();
		if ( $prev ) {
			$prevId = $prev->getId();
		} else {
			$prevId = 0;
		}
		$contentHandler = $this->rev->getContentHandler();
		$de = $contentHandler->createDifferenceEngine( $this->getContext(), $prevId, $this->revId );

		// @todo do something
	}

	function showFooter() {
		$output = $this->getOutput();

		$output->addHtml(
			Html::openElement( 'div', array( 'id' => 'mw-mf-userinfo' ) )
		);

		$userId = $this->rev->getUser();
		if ( $userId ) {
			$user = User::newFromId( $userId );
			$edits = $user->getEditCount();
			$output->addHtml(
				'<div>' .
					Linker::link( $user->getUserPage(), htmlspecialchars( $user->getName() ) ) .
				'</div>' .
				'<div>' .
					$this->listGroups( $user ) .
				'</div>' .
				'<div>' .
					$this->msg( 'mobile-frontend-diffview-editcount', $this->getLang()->formatNum( $edits ) )->escaped() .
				'</div>'
			);
		} else {
			$ipAddr = $this->rev->getUserText();
			$userPage = Title::makeTitle( NS_USER, $ipAddr );
			$output->addHtml(
				'<div>' .
					$this->msg( 'mobile-frontend-diffview-anonymous' )->escaped() .
				'</div>' .
				'<div>' .
					Linker::link( $userPage, htmlspecialchars( $ipAddr ) ) .
				'</div>'
			);
		}
		
		$output->addHtml(
			Html::closeElement( 'div' )
		);
	}

	function listGroups( $user ) {
		# Get groups to which the user belongs
		$userGroups = $user->getGroups();
		$userMembers = array();
		foreach ( $userGroups as $n => $ug ) {
			$memberName = User::getGroupMember( $ug, $user->getName() );
			if ( $n == 0 ) {
				$memberName = $this->getLanguage()->ucfirst( $memberName );
			}
			$userMembers[] = User::makeGroupLinkHTML( $ug, $memberName );
		}

		return $this->getLanguage()->commaList( $userMembers );
	}
}
