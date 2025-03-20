<?php

declare( strict_types = 1 );

namespace ProfessionalWiki\WikibaseConstraintViolationsExport\Maintenance;

use MediaWiki\Language\Language;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MessageLocalizer;
use ProfessionalWiki\WikibaseConstraintViolationsExport\Presentation\PlainTextViolationMessageRenderer;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ConstraintReport\ConstraintCheck\Result\CheckResult;
use WikibaseQuality\ConstraintReport\ConstraintsServices;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class ExportConstraintViolations extends Maintenance implements MessageLocalizer {

	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'Wikibase Quality Constraints Export' );
		$this->addDescription( 'Generates constraints violations in JSON format' );
	}

	public function msg( $key, ...$params ): Message {
		return wfMessage( $key, ...$params );
	}

	public function execute() {
		$entityIdPager = $this->getEntityIdPager();
		$violationMessageRenderer = $this->getViolationMessageRenderer();
		$dataValueFormatter = $this->getValueFormatter(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' )
		);

		$allViolations = [];

		while ( true ) {
			$ids = $entityIdPager->fetchIds( 1 );
			if ( $ids === [] ) {
				break;
			}
			$id = $ids[0];

			$results = ConstraintsServices::getDelegatingConstraintChecker()->checkAgainstConstraintsOnEntityId( $id );

			$violations = [];
			foreach ( $results as $result ) {
				if ( $result->getStatus() === CheckResult::STATUS_COMPLIANCE ) {
					continue;
				}
				$violations[] = [
					'status' => $result->getStatus(),
					'propertyId' => $result->getContextCursor()->getSnakPropertyId(),
					'messageKey' => $result->getMessage()->getMessageKey(),
					'message' => $violationMessageRenderer->render( $result->getMessage() ),
					'constraintId' => $result->getConstraintId(),
					'constraintType' => $result->getConstraint()->getConstraintTypeItemId(),
					'value' => $result->getDataValue() === null ? '' : $dataValueFormatter->format( $result->getDataValue() )
				];
			}
			if ( $violations !== [] ) {
				$allViolations[$id->getSerialization()] = $violations;
			}
		}

		$this->output( json_encode( $allViolations ) );
	}

	private function getEntityIdPager(): EntityIdPager {
		return ( new SqlEntityIdPagerFactory(
			WikibaseRepo::getEntityNamespaceLookup(),
			WikibaseRepo::getEntityIdLookup(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
		) )->newSqlEntityIdPager();
	}

	private function getViolationMessageRenderer(): PlainTextViolationMessageRenderer {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );

		return new PlainTextViolationMessageRenderer(
			entityIdFormatter: WikibaseRepo::getEntityIdLabelFormatterFactory()->getEntityIdFormatter( $language ),
			dataValueFormatter: $this->getValueFormatter( $language ),
			languageNameUtils: MediaWikiServices::getInstance()->getLanguageNameUtils(),
			userLanguageCode: $language->getCode(),
			languageFallbackChain: WikibaseRepo::getLanguageFallbackChainFactory()->newFromLanguage( $language ),
			messageLocalizer: $this,
			config: MediaWikiServices::getInstance()->getMainConfig()
		);
	}

	private function getValueFormatter( Language $language ): ValueFormatter {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( SnakFormatter::OPT_LANG, $language->getCode() );
		return WikibaseRepo::getValueFormatterFactory()->getValueFormatter( SnakFormatter::FORMAT_PLAIN, $formatterOptions );
	}

}

$maintClass = ExportConstraintViolations::class;
require_once RUN_MAINTENANCE_IF_MAIN;
