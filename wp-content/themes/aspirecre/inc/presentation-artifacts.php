<?php
/** Native, editable journey illustrations. Prompts describe work, never completed client records. */
defined( 'ABSPATH' ) || exit;

function aspire_hp_journey_artifact( string $key ): string {
	$artifacts = array(
		'tenant' => array(
			'name' => 'Real Estate Brief',
			'title' => 'Room for<br>what’s next.',
			'fields' => array(
				'Use' => 'How does the space need to work?',
				'Size' => 'What do your plans call for?',
				'Area' => 'Where should the search begin?',
				'Timing' => 'When does the move need to happen?',
				'Priorities' => 'What matters most to the business?',
			),
			'note' => 'Questions to shape the brief with your advisor.',
			'milestones' => 'Selected property → Commercial terms → LOI → Documentation → Handoff / occupancy',
		),
		'owner' => array(
			'name' => 'Asset Assessment',
			'title' => 'The asset.<br>The objective.',
			'fields' => array(
				'Objective' => 'What should the property accomplish?',
				'Position' => 'What is the asset offering today?',
				'Audience' => 'Who could the property serve?',
				'Timing' => 'What needs to happen, and when?',
				'Priorities' => 'Which ownership needs come first?',
			),
			'note' => 'A starting point for an advisor-led asset assessment.',
			'milestones' => 'Positioning → Qualified prospect → Commercial terms → Agreement → Operations',
		),
		'investor' => array(
			'name' => 'Investment Brief',
			'title' => 'A considered<br>investment.',
			'fields' => array(
				'Objective' => 'What is the investment intended to achieve?',
				'Asset' => 'Which property types belong in the search?',
				'Market' => 'Where should opportunity be evaluated?',
				'Criteria' => 'Which assumptions need to be tested?',
				'Horizon' => 'What is the ownership or development plan?',
			),
			'note' => 'Questions to frame the opportunity, before the numbers.',
			'milestones' => 'Opportunity → Commercial terms → Diligence → Closing → Asset strategy',
		),
		'management' => array(
			'name' => 'Asset Review',
			'title' => 'The whole<br>asset.',
			'fields' => array(
				'Operations' => 'What needs attention at the property?',
				'Tenants' => 'Which needs require coordination?',
				'Leasing' => 'How does occupancy support the objective?',
				'Asset plan' => 'Which improvements or decisions lie ahead?',
				'Priorities' => 'What should ownership address first?',
			),
			'note' => 'A starting point for a conversation with the management team.',
			'milestones' => 'Priorities → Responsibilities → Leasing → Asset plan → Reassessment',
		),
	);
	if ( ! isset( $artifacts[ $key ] ) ) { return ''; }
	$artifact = $artifacts[ $key ];
	$document = aspire_hp_p( 'ASPIRE / ' . esc_html( strtoupper( $artifact['name'] ) ), 'hp-document-label' );
	$document .= aspire_hp_p( $artifact['title'], 'hp-document-title' );
	foreach ( $artifact['fields'] as $label => $question ) {
		$document .= aspire_hp_p( '<strong>' . esc_html( $label ) . '</strong><span>' . esc_html( $question ) . '</span>', 'hp-brief-line' );
	}
	$document .= aspire_hp_p( esc_html( $artifact['note'] ), 'hp-document-note' );
	return aspire_hp_group(
		aspire_hp_group( $document, 'hp-artifact-document', '', 'div', $artifact['name'] . ' prompts' ) .
		aspire_hp_p( esc_html( $artifact['milestones'] ), 'hp-artifact-milestones' ),
		'hp-journey-artifact hp-artifact-' . $key,
		'', 'div', $artifact['name'] . ' illustration'
	);
}

/** Topic content can be presented through the shared stage controls without adding a second app. */
function aspire_hp_artifact_topics( string $key ): string {
	$sets = array(
		'tenant' => array(
			'Economics' => 'Understand occupancy costs and the assumptions behind the offer.',
			'Improvements' => 'Clarify the scope, responsibility and timing of the work the space needs.',
			'Timing' => 'Bring delivery, decision dates and occupancy into a workable sequence.',
			'Flexibility' => 'Discuss the options that may matter as the business changes.',
			'Operating obligations' => 'Understand the responsibilities that continue after signing.',
		),
		'owner' => array(
			'Economics' => 'Consider the proposed commercial terms against the ownership objective.',
			'Property commitments' => 'Clarify what ownership and the prospective occupier would each provide.',
			'Timing' => 'Align decisions, delivery and the transition into the property.',
			'Fit' => 'Understand the proposed use and questions that remain before agreement.',
			'Ongoing obligations' => 'Connect the negotiated terms with leasing and operations after signing.',
		),
		'investor' => array(
			'Price and terms' => 'Assess the proposed terms in the context of the investment objective.',
			'Contingencies' => 'Identify the conditions and unresolved questions that need attention.',
			'Timing' => 'Coordinate decision points, diligence and the proposed closing sequence.',
			'Responsibilities' => 'Clarify who must provide information and complete each remaining step.',
			'Asset strategy' => 'Keep the intended ownership or development direction in view.',
		),
		'management' => array(
			'Priorities' => 'Turn the asset plan into a clear sequence of work and decisions.',
			'Responsibilities' => 'Establish who is coordinating each operational need and next action.',
			'Tenant coordination' => 'Keep the people affected by property work informed and involved.',
			'Leasing and improvements' => 'Connect current work with occupancy and the broader asset plan.',
			'Follow-through' => 'Review progress and bring unresolved items back to ownership.',
		),
	);
	if ( ! isset( $sets[ $key ] ) ) { return ''; }
	$content = '';
	foreach ( $sets[ $key ] as $label => $explanation ) {
		$content .= aspire_hp_group(
			aspire_hp_p( esc_html( $label ), 'hp-artifact-topic-label' ) . aspire_hp_p( esc_html( $explanation ), 'hp-artifact-topic-explanation' ),
			'hp-artifact-topic', '', 'div', $label
		);
	}
	return aspire_hp_group( $content, 'hp-artifact-topics', '', 'div', 'management' === $key ? 'Operational execution topics' : 'Commercial negotiation topics' );
}
