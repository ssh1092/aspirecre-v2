<?php
/**
 * Title: Aspire — The client journey
 * Slug: aspirecre/presentation-journey
 * Categories: aspirecre
 */
require_once get_theme_file_path( '/inc/presentation-patterns.php' );

// Native blocks keep every piece of business copy and every stage image editable on Home.
$journeys = array(
	'tenant' => array( 'I need space', 'Tenant Representation', 'Talk to a Tenant Advisor →', array(
		array( 'Define', 'A better move begins with a better brief.', 'Clarify how the business works, what the space must support and when it is needed.', 'Aspire translates use, size, location, timing and priorities into a focused property requirement.', 'A shared brief with essential requirements separated from preferences.', 'Atlas organises the requirement. Your advisor asks what a filter cannot.' ),
		array( 'Explore', 'Put the requirement into Houston.', 'Understand which locations and available spaces merit a closer look.', 'Aspire connects the brief with current opportunities and local market context.', 'A focused starting point, with fit and open questions clearly identified.', 'Atlas brings geography and published property facts together. Your advisor confirms availability and suitability.' ),
		array( 'Compare', 'See the trade-offs. Not just the square feet.', 'Compare physical alternatives against the way your business needs to operate.', 'Aspire considers layout, access, economics and the compromises behind each option.', 'A shortlist that explains where each option works and what still needs confirmation.', 'Structured facts support comparison. Human judgment weighs the practical differences.' ),
		array( 'Validate', 'Look beyond the listing.', 'Confirm that the property can support the intended use before making a commitment.', 'Aspire coordinates property questions, tours and the information needed for a sound decision.', 'A clearer view of loading, parking, improvements, costs and outstanding diligence.', 'The property record collects known facts. Your advisor investigates the unknowns.' ),
		array( 'Negotiate', 'Make the terms work as hard as the space.', 'Balance economics, improvements, timing, flexibility and operating obligations.', 'Aspire represents your objective in the commercial negotiation and coordinates the next steps.', 'A clear understanding of the proposed terms and their implications.', 'Organised information supports the discussion. People negotiate, with legal review where needed.' ),
		array( 'Execute', 'From the right decision to the next chapter.', 'Move from a selected property and agreed commercial direction toward occupancy.', 'Aspire helps coordinate the LOI, documentation, milestones and handoff.', 'A visible path through the remaining decisions and responsibilities.', 'The brief keeps the objective in view. Your advisor stays involved through execution.' ),
	) ),
	'owner' => array( 'I own a property', 'Landlord Representation', 'Talk About Your Property →', array(
		array( 'Assess', 'Start with the asset. And your objective.', 'Understand what the property needs to accomplish for ownership.', 'Aspire reviews the asset, current position and leasing or disposition objective.', 'An assessment of priorities, information gaps and the decisions ahead.', 'Property information establishes a baseline. Local judgment puts it in context.' ),
		array( 'Position', 'Give the right occupier a reason to look.', 'Establish the property’s place in the market and who it can serve.', 'Aspire considers the physical offer, target audience and commercial positioning.', 'A purposeful leasing or sale strategy grounded in the asset.', 'Market context informs positioning. Your advisor interprets its relevance.' ),
		array( 'Market', 'Bring the property to its audience.', 'Turn the positioning into a clear market presence.', 'Aspire presents the property and supports outreach to prospective tenants or buyers.', 'A coherent property story and a route for genuine enquiries.', 'Digital presentation supports discovery. Relationships bring context to the response.' ),
		array( 'Qualify', 'Separate interest from a workable fit.', 'Understand whether a prospect’s needs and proposed use fit the property.', 'Aspire helps clarify requirements, questions and the next conversation.', 'A clearer picture of the opportunity and what must be verified.', 'Organised enquiry information helps. Your advisor qualifies the substance.' ),
		array( 'Negotiate', 'Protect the objective behind the transaction.', 'Consider the trade-offs in economics, timing and obligations.', 'Aspire represents ownership through the commercial discussion.', 'Proposed terms with the ownership implications made clear.', 'The information stays organised. Negotiation remains advisor-led.' ),
		array( 'Operate', 'Think beyond the signed agreement.', 'Connect the transaction with the ongoing needs of the asset.', 'Aspire helps align handoff, leasing and property-management considerations.', 'Clear next responsibilities and an ongoing asset perspective.', 'Records preserve context. People coordinate tenants, ownership and operations.' ),
	) ),
	'investor' => array( 'I’m investing / developing', 'Investment Sales · Investor & Developer Services', 'Discuss an Opportunity →', array(
		array( 'Define', 'An investment starts with a point of view.', 'Clarify the asset, location, scale and objectives behind the investment.', 'Aspire translates the investment or development direction into a search brief.', 'A shared view of the opportunity being sought and the constraints around it.', 'The brief structures requirements. Your advisor challenges the assumptions.' ),
		array( 'Source', 'Look at the opportunity in its setting.', 'Identify properties worth evaluating within the wider Houston market.', 'Aspire brings current opportunities, relationships and local context to the search.', 'A considered starting pipeline, with questions to investigate.', 'Atlas reveals spatial context. Advisors assess relevance beyond a map pin.' ),
		array( 'Underwrite', 'Understand what the opportunity depends on.', 'Examine the assumptions that shape the investment case.', 'Aspire helps organise property information and identify analysis still required.', 'A clearer view of inputs, uncertainty and the conditions for proceeding.', 'Structured information supports analysis. Investment judgment remains with people.' ),
		array( 'Diligence', 'Test the assumptions against the asset.', 'Investigate the property, intended use and unresolved risks.', 'Aspire coordinates questions and information with the relevant parties.', 'An informed decision about the opportunity and the remaining unknowns.', 'The record makes gaps visible. Advisors and specialists investigate them.' ),
		array( 'Negotiate', 'Align the terms with the investment.', 'Balance price, timing, contingencies and the allocation of obligations.', 'Aspire represents the commercial objective through negotiation.', 'A clear basis for assessing terms and moving into documentation.', 'Information supports the decision. Advisors negotiate and specialists review.' ),
		array( 'Execute', 'Carry the strategy into ownership.', 'Coordinate the remaining transaction milestones and asset direction.', 'Aspire helps connect documentation, closing and the next phase of the property.', 'A defined path from acquisition toward the ownership or development plan.', 'The brief preserves intent. Human coordination turns it into action.' ),
	) ),
	'management' => array( 'I need property management', 'Property Management · CRE Consulting', 'Talk About Property Management →', array(
		array( 'Review', 'See the whole asset before the next task.', 'Understand the property’s operating needs and the ownership objective.', 'Aspire reviews available records, tenant needs and current priorities.', 'A shared view of the asset and what needs attention.', 'Property information supports the review. People establish the priorities.' ),
		array( 'Stabilize', 'Bring the immediate priorities into focus.', 'Address the operational issues that need clear ownership and follow-through.', 'Aspire helps coordinate day-to-day needs and tenant communication.', 'An organised set of priorities, responsibilities and next actions.', 'Records keep context visible. Your management team handles the response.' ),
		array( 'Lease', 'Connect operations with occupancy.', 'Consider how leasing activity supports the asset’s direction.', 'Aspire brings property-management knowledge into leasing and tenant coordination.', 'An aligned view of the property offer and operational requirements.', 'Current property information helps presentation. Advisors assess the fit.' ),
		array( 'Plan', 'Give the asset a considered next chapter.', 'Look ahead to property needs, improvements and ownership priorities.', 'Aspire helps frame the asset plan and the decisions needed to support it.', 'A practical direction with assumptions and open questions identified.', 'Information supports planning. Ownership and advisors set the direction.' ),
		array( 'Execute', 'Make the plan visible in the everyday.', 'Coordinate the agreed priorities across the people involved.', 'Aspire supports implementation, communication and follow-through.', 'A clear view of responsibilities and progress through the work.', 'Shared records maintain continuity. People manage execution.' ),
		array( 'Reassess', 'Keep the property and the objective aligned.', 'Review what has changed and what the asset needs next.', 'Aspire revisits operations, leasing and the broader ownership direction.', 'An updated set of priorities for the next stage of the asset.', 'The record preserves the history. Human judgment determines the next move.' ),
	) ),
);
$groups = array(
	'tenant' => array( 'FIND THE RIGHT OPTIONS', 'MAKE THE DECISION', 'GET THE DEAL DONE' ),
	'owner' => array( 'POSITION THE PROPERTY', 'FIND THE RIGHT FIT', 'DELIVER THE OUTCOME' ),
	'investor' => array( 'FIND THE RIGHT OPTIONS', 'MAKE THE DECISION', 'GET THE DEAL DONE' ),
	'management' => array( 'UNDERSTAND THE ASSET', 'PLAN THE NEXT MOVE', 'PUT THE PLAN TO WORK' ),
);

$stage_visual = static function ( string $journey, int $index, array $stage ): string {
	$briefs = array(
		'tenant' => array( 'Real Estate Brief', 'Room for<br>what’s next.', array( 'Use' => 'How the property needs to work', 'Size' => 'The space your plans call for', 'Area' => 'Where the opportunity belongs', 'Timing' => 'When the move needs to happen', 'Priorities' => 'What matters most to you' ), 'A starting point for a conversation.' ),
		'owner' => array( 'Asset Assessment', 'The asset.<br>The objective.', array( 'Objective' => 'What should the property accomplish?', 'Position' => 'What is the asset offering today?', 'Audience' => 'Who could the property serve?', 'Timing' => 'What needs to happen, and when?', 'Priorities' => 'Which ownership needs come first?' ), 'A starting point for an advisor-led asset assessment.' ),
		'investor' => array( 'Investment Brief', 'A considered<br>investment.', array( 'Objective' => 'What is the investment intended to achieve?', 'Asset' => 'Which property types belong in the search?', 'Market' => 'Where should opportunity be evaluated?', 'Criteria' => 'Which assumptions need to be tested?', 'Horizon' => 'What is the ownership or development plan?' ), 'Questions to frame the opportunity, before the numbers.' ),
		'management' => array( 'Asset Review', 'The whole<br>asset.', array( 'Operations' => 'What needs attention at the property?', 'Tenants' => 'Which needs require coordination?', 'Leasing' => 'How does occupancy support the objective?', 'Asset plan' => 'Which improvements or decisions lie ahead?', 'Priorities' => 'What should ownership address first?' ), 'A starting point for a conversation with the management team.' ),
	);
	$milestones = array(
		'tenant' => 'Selected property → Commercial terms → LOI → Documentation → Handoff / occupancy',
		'owner' => 'Positioning → Qualified prospect → Commercial terms → Agreement → Operations',
		'investor' => 'Opportunity → Commercial terms → Diligence → Closing → Asset strategy',
		'management' => 'Priorities → Responsibilities → Leasing → Asset plan → Reassessment',
	);
	$terms = array(
		'tenant' => array( 'Economics', 'Improvements', 'Timing', 'Flexibility', 'Operating obligations' ),
		'owner' => array( 'Economics', 'Property commitments', 'Timing', 'Fit', 'Ongoing obligations' ),
		'investor' => array( 'Price and terms', 'Contingencies', 'Timing', 'Responsibilities', 'Asset strategy' ),
		'management' => array( 'Priorities', 'Responsibilities', 'Tenant coordination', 'Leasing and improvements', 'Follow-through' ),
	);
	$questions = array(
		'tenant' => array( 'Loading / access?', 'Parking / circulation?', 'Improvements / fit?', 'Costs / obligations?' ),
		'owner' => array( 'Property position?', 'Prospect fit?', 'Information gaps?', 'Ownership objective?' ),
		'investor' => array( 'Property condition?', 'Intended use?', 'Unresolved risks?', 'Information gaps?' ),
		'management' => array( 'Operating needs?', 'Tenant needs?', 'Current priorities?', 'Ownership direction?' ),
	);
	$make_list = static function ( array $items, string $class ): string {
		$list = '';
		foreach ( $items as $item ) { $list .= '<li>' . esc_html( $item ) . '</li>'; }
		return aspire_hp_block( 'list', array( 'className' => $class ), '<ul class="wp-block-list ' . esc_attr( $class ) . '">' . $list . '</ul>' );
	};

	if ( 0 === $index ) {
		$brief = $briefs[ $journey ];
		$document = aspire_hp_p( 'ASPIRE / ' . strtoupper( $brief[0] ), 'hp-document-label' ) . aspire_hp_p( $brief[1], 'hp-document-title' );
		foreach ( $brief[2] as $label => $value ) { $document .= aspire_hp_p( '<strong>' . esc_html( $label ) . '</strong><span>' . esc_html( $value ) . '</span>', 'hp-brief-line' ); }
		$document .= aspire_hp_p( $brief[3], 'hp-document-note' );
		// The approved property photograph belongs only to the Tenant Define brief.
		$image = 'tenant' === $journey ? aspire_hp_image( 116, 'hp-stage-image', 'Industrial and flex property in Houston' ) : '';
		return aspire_hp_group( $image . aspire_hp_group( $document, 'hp-integrated-brief', '', 'div', $brief[0] . ' prompts' ), 'hp-stage-visual hp-visual-brief', '', 'div', $brief[0] . ' visual' );
	}
	if ( 'tenant' !== $journey && $index < 4 ) {
		$artifact = aspire_hp_p( sprintf( '%02d / %s', $index + 1, esc_html( $stage[0] ) ), 'hp-document-label' ) . aspire_hp_h( $stage[1], 4, 'hp-artifact-heading' );
		foreach ( array( 'Decision' => $stage[2], 'Aspire' => $stage[3], 'Outcome' => $stage[4] ) as $label => $text ) {
			$artifact .= aspire_hp_group( aspire_hp_p( $label, 'hp-work-label' ) . aspire_hp_p( $text, 'hp-work-copy' ), 'hp-work-step' );
		}
		return aspire_hp_group( aspire_hp_group( $artifact, 'hp-static-stage-work', '', 'div', $stage[0] . ' working artifact' ), 'hp-stage-visual hp-visual-stage-work', '', 'div', $stage[0] . ' visual' );
	}
	if ( 1 === $index ) {
		$artifact = aspire_hp_p( 'HOUSTON', 'hp-map-place' ) . aspire_hp_group( aspire_hp_p( 'Requirement', 'hp-map-node' ) . aspire_hp_p( 'Geography', 'hp-map-node' ) . aspire_hp_p( 'Potential properties', 'hp-map-node' ), 'hp-map-path' );
		$artifact .= aspire_hp_p( $stage[4], 'hp-artifact-note' );
		return aspire_hp_group( aspire_hp_group( $artifact, 'hp-static-map-artifact', '', 'div', 'Houston search context' ), 'hp-stage-visual hp-visual-search', '', 'div', 'Search and geography visual' );
	}
	if ( 2 === $index ) {
		$artifact = aspire_hp_p( 'PROPERTY COMPARISON', 'hp-document-label' ) . aspire_hp_h( $stage[1], 4, 'hp-artifact-heading' );
		$artifact .= aspire_hp_group( aspire_hp_p( 'Physical fit', 'hp-comparison-heading' ) . aspire_hp_p( 'Known property information', 'hp-comparison-copy' ), 'hp-comparison-column' );
		$artifact .= aspire_hp_group( aspire_hp_p( 'Business fit', 'hp-comparison-heading' ) . aspire_hp_p( 'Trade-offs and questions to confirm', 'hp-comparison-copy' ), 'hp-comparison-column' );
		$artifact .= aspire_hp_p( 'Suitability has not been determined.', 'hp-artifact-note' );
		return aspire_hp_group( aspire_hp_group( $artifact, 'hp-static-comparison', '', 'div', 'Property comparison framework' ), 'hp-stage-visual hp-visual-compare', '', 'div', 'Property comparison visual' );
	}
	if ( 3 === $index ) {
		$artifact = aspire_hp_p( 'QUESTIONS TO VALIDATE', 'hp-document-label' ) . aspire_hp_h( $stage[1], 4, 'hp-artifact-heading' ) . $make_list( $questions[ $journey ], 'hp-question-list' );
		return aspire_hp_group( aspire_hp_group( $artifact, 'hp-static-diligence', '', 'div', 'Validation questions' ), 'hp-stage-visual hp-visual-diligence', '', 'div', 'Diligence visual' );
	}
	if ( 4 === $index ) {
		$artifact = aspire_hp_p( 'COMMERCIAL TERMS', 'hp-document-label' ) . aspire_hp_h( $stage[1], 4, 'hp-artifact-heading' ) . $make_list( $terms[ $journey ], 'hp-terms-list');
		$artifact .= aspire_hp_p( 'Your advisor weighs the whole agreement.', 'hp-artifact-note' );
		return aspire_hp_group( aspire_hp_group( $artifact, 'hp-static-terms', '', 'div', 'Commercial negotiation topics' ), 'hp-stage-visual hp-visual-terms', '', 'div', 'Commercial terms visual' );
	}
	$artifact = aspire_hp_p( 'A CLEAR PATH TO WHAT’S NEXT.', 'hp-document-label' ) . aspire_hp_h( $stage[1], 4, 'hp-artifact-heading' ) . $make_list( explode( ' → ', $milestones[ $journey ] ), 'hp-milestone-list' );
	return aspire_hp_group( aspire_hp_group( $artifact, 'hp-static-milestones', '', 'div', 'Execution milestones' ), 'hp-stage-visual hp-visual-execute', '', 'div', 'Execution milestones visual' );
};

$selector = '';
foreach ( $journeys as $key => $journey ) {
	$selector .= aspire_hp_p( '<button type="button" class="hp-objective-button" role="tab" aria-selected="false" aria-controls="journey-' . esc_attr( $key ) . '" data-journey="' . esc_attr( $key ) . '">' . esc_html( $journey[0] ) . '</button>', 'hp-objective-link' );
}
$header = aspire_hp_p( 'YOUR NEXT MOVE', 'hp-orientation-eyebrow' ) . aspire_hp_h( 'Here’s how Aspire helps you get there.', 2, 'hp-orientation-heading' ) . aspire_hp_group( $selector, 'hp-objectives', '', 'div', 'Choose a client objective' );

$tracks = '';
$service_anchors = array(
	'tenant' => array( 'tenant-representation' ),
	'owner' => array( 'landlord-representation' ),
	'investor' => array( 'investment-sales', 'investor-developer-services' ),
	'management' => array( 'property-management', 'cre-consulting' ),
);
foreach ( $journeys as $key => $journey ) {
	$nav = '';
	$panels = '';
	$anchors = '';
	foreach ( $service_anchors[ $key ] as $anchor ) { $anchors .= aspire_hp_group( '', 'hp-service-anchor', $anchor, 'div', $journey[1] . ' anchor' ); }
	foreach ( $journey[3] as $index => $stage ) {
		$id = $key . '-' . sanitize_title( $stage[0] );
		$nav .= aspire_hp_p( '<button type="button" role="tab" aria-selected="false" aria-controls="' . esc_attr( $id ) . '-panel" data-stage="' . $index . '"><span>' . sprintf( '%02d', $index + 1 ) . '</span> ' . esc_html( $stage[0] ) . '</button>', 'hp-stage-link' );
		$copy = aspire_hp_p( sprintf( '%02d / %s', $index + 1, esc_html( $stage[0] ) ), 'hp-stage-number' ) . aspire_hp_h( $stage[1], 3, 'hp-stage-statement' );
		$copy .= aspire_hp_group( aspire_hp_h( 'The decision', 4, 'hp-copy-label' ) . aspire_hp_p( $stage[2] ), 'hp-stage-detail hp-stage-client' );
		$copy .= aspire_hp_group( aspire_hp_h( 'Aspire handles', 4, 'hp-copy-label' ) . aspire_hp_p( $stage[3] ), 'hp-stage-detail hp-stage-advisor-copy' );
		$copy .= aspire_hp_group( aspire_hp_h( 'You get', 4, 'hp-copy-label' ) . aspire_hp_p( $stage[4] ), 'hp-stage-detail hp-stage-outcome' );
		if ( '' !== $stage[5] ) { $copy .= aspire_hp_group( aspire_hp_h( 'Technology', 4, 'hp-copy-label' ) . aspire_hp_p( $stage[5] ), 'hp-stage-detail hp-stage-technology' ); }
		$copy .= aspire_hp_link( $journey[2], 'tel:+17139332001', 'hp-journey-advisor hp-link' );
		$controls = aspire_hp_p( '<button type="button" class="hp-stage-prev">Previous</button><button type="button" class="hp-stage-next">Next</button>', 'hp-step-controls' );
		$panel = aspire_hp_group( aspire_hp_group( $copy . $controls, 'hp-stage-information' ) . $stage_visual( $key, $index, $stage ), 'hp-stage-panel', $id . '-panel', 'div', '', 'full' );
		$accordion = aspire_hp_p( '<button type="button" class="hp-accordion-trigger" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '-panel"><span>' . sprintf( '%02d', $index + 1 ) . '</span> ' . esc_html( $stage[0] ) . '</button>', 'hp-accordion-heading' );
		$panels .= aspire_hp_group( $accordion . $panel, 'hp-stage', $id, 'section', $journey[0] . ' — ' . $stage[0], 'full' );
	}
	$stage_groups = '';
	foreach ( $groups[ $key ] as $i => $group ) { $stage_groups .= aspire_hp_p( $group, 'hp-stage-group hp-stage-group-' . ( $i + 1 ) ); }
	$track = $anchors . aspire_hp_h( $journey[1], 3, 'hp-journey-service' ) . aspire_hp_group( $stage_groups . $nav, 'hp-stage-navigation', '', 'div', 'Journey stages' ) . aspire_hp_group( $panels, 'hp-stage-copy', '', 'div', '', 'full' );
	$tracks .= aspire_hp_group( $track, 'hp-journey-track hp-track-' . $key, 'journey-' . $key, 'section', $journey[0], 'full' );
}

echo aspire_hp_group( aspire_hp_group( $header . $tracks, 'hp-shell hp-integrated-journey', 'expertise', 'div', '', 'full' ), 'hp-section hp-journey', 'client-journey', 'section', 'Client journey experience' );
