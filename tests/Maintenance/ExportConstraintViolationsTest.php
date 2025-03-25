<?php

declare( strict_types = 1 );

namespace ProfessionalWiki\WikibaseConstraintViolationsExport\Tests\Maintenance;

use DataValues\StringValue;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use ProfessionalWiki\WikibaseConstraintViolationsExport\Maintenance\ExportConstraintViolations;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\WikibaseRepo;
use WikibaseQuality\ConstraintReport\Tests\DefaultConfig;

/**
 * @covers \ProfessionalWiki\WikibaseConstraintViolationsExport\Maintenance\ExportConstraintViolations
 * @group Database
 */
class ExportConstraintViolationsTest extends MaintenanceBaseTestCase {

	use DefaultConfig;

	private const MULTI_VALUE_CONSTRAINT_ID = '1';
	private const SINGLE_VALUE_CONSTRAINT_ID = '2';

	private const MULTI_VALUE_CONSTRAINT_TYPE_ID = 'Q1';
	private const SINGLE_VALUE_CONSTRAINT_TYPE_ID = 'Q2';

	private const MULTI_VALUE_PROPERTY_ID = 'P1';
	private const SINGLE_VALUE_PROPERTY_ID = 'P2';

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValues( [
			'WBQualityConstraintsMultiValueConstraintId' => self::MULTI_VALUE_CONSTRAINT_TYPE_ID,
			'WBQualityConstraintsSingleValueConstraintId' => self::SINGLE_VALUE_CONSTRAINT_TYPE_ID
		] );
	}

	protected function getMaintenanceClass(): string {
		return ExportConstraintViolations::class;
	}

	public function addDBDataOnce() {
		$this->saveProperty( self::MULTI_VALUE_PROPERTY_ID, 'string', 'Multiple Values' );
		$this->saveProperty( self::SINGLE_VALUE_PROPERTY_ID, 'string', 'Single Value' );

		$this->db->insert(
			'wbqc_constraints',
			[
				[
					'constraint_guid' => self::MULTI_VALUE_CONSTRAINT_ID,
					'pid' => ( new NumericPropertyId( self::MULTI_VALUE_PROPERTY_ID ) )->getNumericId(),
					'constraint_type_qid' => self::MULTI_VALUE_CONSTRAINT_TYPE_ID,
					'constraint_parameters' => '{}',
				],
				[
					'constraint_guid' => self::SINGLE_VALUE_CONSTRAINT_ID,
					'pid' => ( new NumericPropertyId( self::SINGLE_VALUE_PROPERTY_ID ) )->getNumericId(),
					'constraint_type_qid' => self::SINGLE_VALUE_CONSTRAINT_TYPE_ID,
					'constraint_parameters' => '{}',
				]
			]
		);
	}

	public function testExportsEmptyArrayIfThereAreNoViolations() : void{
		$item1 = new Item( new ItemId( 'Q100' ) );
		$this->addStatement( $item1, self::MULTI_VALUE_PROPERTY_ID, 'multi value 1' );
		$this->addStatement( $item1, self::MULTI_VALUE_PROPERTY_ID, 'multi value 2' );
		$this->addStatement( $item1, self::SINGLE_VALUE_PROPERTY_ID, 'single value 1' );

		$this->maintenance->execute();

		$this->expectOutputString( '[]' );
	}

	public function testExportsViolations() : void{
		$item1 = new Item( new ItemId( 'Q100' ) );
		$this->addStatement( $item1, self::MULTI_VALUE_PROPERTY_ID, 'multi value 1' );
		$this->addStatement( $item1, self::SINGLE_VALUE_PROPERTY_ID, 'single value 1' );

		$item2 = new Item( new ItemId( 'Q200' ) );
		$this->addStatement( $item2, self::MULTI_VALUE_PROPERTY_ID, 'multi value 1' );
		$this->addStatement( $item2, self::MULTI_VALUE_PROPERTY_ID, 'multi value 2' );
		$this->addStatement( $item2, self::SINGLE_VALUE_PROPERTY_ID, 'single value 1' );
		$this->addStatement( $item2, self::SINGLE_VALUE_PROPERTY_ID, 'single value 2' );

		$this->maintenance->execute();

		$this->assertEqualsCanonicalizing(
			[
				'Q100' => [
					[
						'status' => 'warning',
						'propertyId' => self::MULTI_VALUE_PROPERTY_ID,
						'messageKey' => 'wbqc-violation-message-multi-value',
						'message' => 'This property should contain multiple values.',
						'constraintId' => self::MULTI_VALUE_CONSTRAINT_ID,
						'constraintType' => self::MULTI_VALUE_CONSTRAINT_TYPE_ID,
						'value' => 'multi value 1'
					]
				],
				'Q200' => [
					[
						'status' => 'warning',
						'propertyId' => self::SINGLE_VALUE_PROPERTY_ID,
						'messageKey' => 'wbqc-violation-message-single-value',
						'message' => 'This property should only contain a single value.',
						'constraintId' => self::SINGLE_VALUE_CONSTRAINT_ID,
						'constraintType' => self::SINGLE_VALUE_CONSTRAINT_TYPE_ID,
						'value' => 'single value 2'
					],
					[
						'status' => 'warning',
						'propertyId' => self::SINGLE_VALUE_PROPERTY_ID,
						'messageKey' => 'wbqc-violation-message-single-value',
						'message' => 'This property should only contain a single value.',
						'constraintId' => self::SINGLE_VALUE_CONSTRAINT_ID,
						'constraintType' => self::SINGLE_VALUE_CONSTRAINT_TYPE_ID,
						'value' => 'single value 1'
					]
				]
			],
			json_decode( $this->getActualOutput(), true )
		);
	}

	protected function saveEntity( EntityDocument $entity ): void {
		WikibaseRepo::getEntityStore()->saveEntity(
			entity: $entity,
			summary: __CLASS__,
			user: self::getTestSysop()->getUser()
		);
	}

	protected function saveProperty( string $pId, string $type, string $label ): void {
		$this->saveEntity(
			new Property(
				id: new NumericPropertyId( $pId ),
				fingerprint: new Fingerprint( labels: new TermList( [
					new Term( languageCode: 'en', text: $label )
				] ) ),
				dataTypeId: $type
			)
		);
	}

	protected function addStatement( EntityDocument $entity, string $propertyId, string $value ): void {
		$statementGuidGenerator = new GuidGenerator();

		$dataValue = new StringValue( $value );
		$snak = new PropertyValueSnak( new NumericPropertyId( $propertyId ), $dataValue );
		$statement = new Statement( $snak );
		$statementGuid = $statementGuidGenerator->newGuid( $entity->getId() );
		$statement->setGuid( $statementGuid );

		$entity->getStatements()->addStatement( $statement );

		$this->saveEntity( $entity );
	}

}
