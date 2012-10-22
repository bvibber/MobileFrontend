<?php

class SpecialMobileWatchlist extends SpecialWatchlist {
	function execute( $par ) {
		$user = $this->getUser();
		$output = $this->getOutput();

		if( $user->isAnon() ) {
			// No watchlist for you.
			return parent::execute( $par );
		}

		$output->addModules( 'mobile.watchlist' );
		$this->showHeader();
		
		$res = $this->doQuery();
		$this->showResults( $res );
	}

	function showHeader() {
		$output = $this->getOutput();
		$output->addHtml(
			Html::openElement( 'ul', array( 'class' => 'mw-mf-watchlist-selector' ) ) .
				Html::element( 'li', array( 'class' => 'selected' ), 'All' ) .
				Html::element( 'li', array(), 'Articles' ) .
				Html::element( 'li', array(), 'Talk' ) .
				Html::element( 'li', array(), 'Other' ) .
			Html::closeElement( 'ul' )
		);
	}
	
	function doQuery() {
		$user = $this->getUser();
		$dbr = wfGetDB( DB_SLAVE, 'watchlist' );

		# Possible where conditions
		$conds = array();

		// snip....

		$tables = array( 'recentchanges', 'watchlist' );
		$fields = array( $dbr->tableName( 'recentchanges' ) . '.*' );
		$join_conds = array(
			'watchlist' => array(
				'INNER JOIN',
				array(
					'wl_user' => $user->getId(),
					'wl_namespace=rc_namespace',
					'wl_title=rc_title'
				),
			),
		);
		$options = array( 'ORDER BY' => 'rc_timestamp DESC' );

		$rollbacker = $user->isAllowed('rollback');
		if ( $usePage || $rollbacker ) {
			$tables[] = 'page';
			$join_conds['page'] = array('LEFT JOIN','rc_cur_id=page_id');
			if ( $rollbacker ) {
				$fields[] = 'page_latest';
			}
		}

		ChangeTags::modifyDisplayQuery( $tables, $fields, $conds, $join_conds, $options, '' );
		wfRunHooks('SpecialWatchlistQuery', array(&$conds,&$tables,&$join_conds,&$fields) );

		$res = $dbr->select( $tables, $fields, $conds, __METHOD__, $options, $join_conds );
		
		return $res;
	}

	function showResults( $res ) {
		$output = $this->getOutput();
		$output->addHtml( '<ul class="mw-mf-watchlist-results">' );
		foreach( $res as $row ) {
			$this->showResultRow( $row );
		}
		$output->addHtml( '</ul>' );
	}

	function showResultRow( $row ) {
		$output = $this->getOutput();

		$title = Title::makeTitle( $row->rc_namespace, $row->rc_title );
		$comment = $row->rc_comment;
		$userId = $row->rc_user;
		$username = $row->rc_user_text;
		$timestamp = $row->rc_timestamp;
		$ts = new MWTimestamp( $row->rc_timestamp );
		$relativeTime = $ts->getHumanTimestamp();

		if ( $userId == 0 ) {
			$usernameChunk = Html::element( 'span',
				array( 'class' => 'mw-mf-ip' ),
				$this->msg( 'mobile-frontend-changeslist-ip' )->plain()
			);
		} else {
			$usernameChunk = htmlspecialchars( $username );
		}

		if ( $comment === '' ) {
			$comment = $this->msg( 'mobile-frontend-changeslist-nocomment' )->plain();
		} else {
			$comment = Linker::formatComment( $comment, $title );
			// flatten back to text
			$comment = Sanitizer::stripAllTags( $comment );
		}

		$output->addHtml(
			'<li>' .
			Html::element( 'div', array( 'class' => 'mw-mf-title' ), $title->getPrefixedText() ).
			Html::openElement( 'div', array( 'class' => 'mw-mf-user' ) ).
				$usernameChunk .
			Html::closeElement( 'div' ) .
			Html::element( 'div', array( 'class' => 'mw-mf-comment' ), $comment ) .
			Html::element( 'div', array( 'class' => 'mw-mf-time' ), $relativeTime ) .
			'</li>'
		);
	}

}
