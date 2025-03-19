<?php

declare( strict_types = 1 );

namespace ProfessionalWiki\WikibaseQualityConstraintsExport\Maintenance;

use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MessageLocalizer;
use ProfessionalWiki\WikibaseQualityConstraintsExport\Presentation\PlainTextViolationMessageRenderer;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ConstraintReport\ConstraintCheck\Message\ViolationMessageRenderer;
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
					'property' => $result->getContextCursor()->getSnakPropertyId(),
					'messageKey' => $result->getMessage()->getMessageKey(),
					'message' => $violationMessageRenderer->render( $result->getMessage() ),
					'constraint' => $result->getConstraintId(),
				];
			}
			if ( $violations !== [] ) {
				$allViolations[$id->getSerialization()] = $violations;
			}
		}

		print( json_encode( $allViolations ) );
	}

	private function getEntityIdPager(): EntityIdPager {
		return ( new SqlEntityIdPagerFactory(
			WikibaseRepo::getEntityNamespaceLookup(),
			WikibaseRepo::getEntityIdLookup(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
		) )->newSqlEntityIdPager();
	}

	private function getViolationMessageRenderer(): ViolationMessageRenderer {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );

		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( SnakFormatter::OPT_LANG, $language->getCode() );

		return new PlainTextViolationMessageRenderer(
			entityIdFormatter: WikibaseRepo::getEntityIdLabelFormatterFactory()->getEntityIdFormatter( $language ),
			dataValueFormatter: WikibaseRepo::getValueFormatterFactory()->getValueFormatter( SnakFormatter::FORMAT_HTML, $formatterOptions ),
			languageNameUtils: MediaWikiServices::getInstance()->getLanguageNameUtils(),
			userLanguageCode: $language->getCode(),
			languageFallbackChain: WikibaseRepo::getLanguageFallbackChainFactory()->newFromLanguage( $language ),
			messageLocalizer: $this,
			config: MediaWikiServices::getInstance()->getMainConfig()
		);
	}

}

$maintClass = ExportConstraintViolations::class;
require_once RUN_MAINTENANCE_IF_MAIN;
