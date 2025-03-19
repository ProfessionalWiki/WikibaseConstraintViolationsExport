<?php

declare( strict_types = 1 );

namespace ProfessionalWiki\WikibaseQualityConstraintsExport\Presentation;

use WikibaseQuality\ConstraintReport\ConstraintCheck\Message\ViolationMessageRenderer;

class PlainTextViolationMessageRenderer extends ViolationMessageRenderer {

	protected function addRole( string $value, ?string $role ): string {
		if ( $role === null ) {
			return "'$value'";
		}

		return "'$value [$role]'";
	}

	// TODO: renderList

	// TODO: renderItemIdSnakValue

	// TODO: renderInlineCode
}
