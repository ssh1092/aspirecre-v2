<?php
/**
 * Title: Aspire — The client journey
 * Slug: aspirecre/presentation-journey
 * Categories: aspirecre
 */
require_once get_theme_file_path( '/inc/presentation-patterns.php' );
require_once get_theme_file_path( '/inc/presentation-artifacts.php' );
// The approved sequences, authored as ordinary headings and paragraphs in Gutenberg.
$journeys = array(
	'tenant' => array( 'I need space', 'Tenant Representation', 'Talk to a Tenant Advisor →', array(
		array( 'Define', 'A better move begins with a better brief.', 'Clarify how your business works, what the space must support and when you need it.', 'Aspire translates use, size, location, timing and priorities into a focused requirement.', 'A shared brief, with the essentials separated from the preferences.', 'Atlas organises the requirement. Your advisor asks what a filter cannot.' ),
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
$intro = aspire_hp_p( 'YOUR NEXT MOVE', 'hp-orientation-eyebrow' ) . aspire_hp_h( 'Here’s how Aspire helps you get there.', 2, 'hp-orientation-heading' );
$selector = '';
foreach ( $journeys as $key => $journey ) { $selector .= aspire_hp_link( $journey[0] . ' ↗', '#journey-' . $key, 'hp-objective-link' ); }
$intro .= aspire_hp_group( $selector, 'hp-objectives', '', 'div', 'Choose a client objective' );
$chapter_titles = array(
 'tenant' => array( 'Find the right options', 'Make the decision', 'Get the deal done' ),
 'owner' => array( 'Position the property', 'Find the right fit', 'Deliver the outcome' ),
 'investor' => array( 'Find the right options', 'Make the decision', 'Get the deal done' ),
 'management' => array( 'Understand the asset', 'Plan the next move', 'Put the plan to work' ),
);
foreach ( $journeys as $key => $journey ) {
 $chapters = '';
 foreach ( $chapter_titles[$key] as $chapter => $title ) {
  $steps = '';
  foreach ( array( $chapter * 2, $chapter * 2 + 1 ) as $index ) {
   $name = $journey[3][$index][0];
   $steps .= aspire_hp_link( sprintf( '%02d', $index + 1 ) . ' ' . $name, '#' . $key . '-' . sanitize_title( $name ), 'hp-orientation-step' );
  }
  $chapters .= aspire_hp_group( aspire_hp_h( $title, 3, 'hp-chapter-heading' ) . aspire_hp_group( $steps, 'hp-chapter-steps' ), 'hp-orientation-chapter' );
 }
 $first = $journey[3][0][0];
 $intro .= aspire_hp_group( aspire_hp_p( $journey[1], 'hp-orientation-service' ) . aspire_hp_group( $chapters, 'hp-orientation-chapters' ) . aspire_hp_link( 'Start with ' . $first . ' ↓', '#' . $key . '-' . sanitize_title( $first ), 'hp-orientation-start' ), 'hp-orientation-path hp-orientation-' . $key );
}

$brief = aspire_hp_p( 'ASPIRE / REAL ESTATE BRIEF', 'hp-document-label' ) . aspire_hp_p( 'Room for<br>what’s next.', 'hp-document-title' );
foreach ( array( 'Use' => 'How the property needs to work', 'Size' => 'The space your plans call for', 'Area' => 'Where the opportunity belongs', 'Timing' => 'When the move needs to happen', 'Priorities' => 'What matters most to you' ) as $label => $value ) { $brief .= aspire_hp_p( '<strong>' . $label . '</strong><span>' . $value . '</span>', 'hp-brief-line' ); }
$brief .= aspire_hp_p( 'A starting point for a conversation.', 'hp-document-note' );
$scene = aspire_hp_image( 116, 'hp-scene-photo hp-native-photo', 'Industrial / flex architecture at 16840 Clay Road, Houston' );
$scene .= aspire_hp_group( aspire_hp_p( 'Houston', 'hp-map-place' ) . aspire_hp_p( 'A requirement becomes a place to look.', 'hp-map-caption' ) . aspire_hp_link( 'Open interactive Aspire Atlas ↗', '#aspire-atlas' ), 'hp-scene-map', '', 'div', 'Atlas reference view' );
$scene .= aspire_hp_group( $brief, 'hp-brief-object', '', 'div', 'Real Estate Brief illustration' );
$annotations = '';
foreach ( array( 'Loading / access?' => 'Can the property accommodate the vehicles, deliveries and daily access the intended use requires?', 'Parking / circulation?' => 'How will customers, staff and service vehicles move through the property?', 'Improvements / fit?' => 'What work or permissions would the intended use require, and who would be responsible?', 'Costs / obligations?' => 'Which operating costs and responsibilities need to be confirmed before proceeding?' ) as $text => $detail ) { $annotations .= aspire_hp_group( aspire_hp_p( $text, 'hp-question-title' ) . aspire_hp_p( $detail, 'hp-question-detail' ), 'hp-property-question' ); }
$scene .= aspire_hp_group( $annotations, 'hp-diligence-notes', '', 'div', 'Questions to validate' );
$terms = '';
foreach ( array( 'Economics', 'Improvements', 'Timing', 'Flexibility', 'Operating obligations' ) as $i => $text ) { $terms .= aspire_hp_link( $text, '#negotiation-' . $i, 'hp-term-link' ); }
$term_copy = '';
foreach ( array( 'Understand total occupancy economics and the assumptions behind the offer.', 'Clarify the scope, responsibility and timing of the work the property needs.', 'Bring delivery, decision dates and occupancy into one workable sequence.', 'Discuss the options that may matter as your business changes.', 'Understand maintenance, operating expenses and the responsibilities that continue after signing.' ) as $i => $text ) { $term_copy .= aspire_hp_group( aspire_hp_p( $text ), 'hp-term-explanation', 'negotiation-' . $i ); }
$scene .= aspire_hp_group( $terms . $term_copy . aspire_hp_p( 'Your advisor weighs the whole agreement.', 'hp-terms-note' ), 'hp-terms-object', '', 'div', 'Commercial negotiation topics' );
$scene .= aspire_hp_group( aspire_hp_p( 'A clear path<br>to what’s next.', 'hp-closure-title' ) . aspire_hp_p( 'Selected property → Commercial terms → LOI → Documentation → Handoff / occupancy', 'hp-milestones' ), 'hp-execute-object', '', 'div', 'Execution milestones' );
$tracks = '';
foreach ( $journeys as $key => $journey ) {
	$nav = '';
	$stages = '';
	foreach ( $journey[3] as $index => $stage ) {
		$anchor = $key . '-' . sanitize_title( $stage[0] );
		$nav .= aspire_hp_link( sprintf( '%02d', $index + 1 ) . ' ' . $stage[0], '#' . $anchor, 'hp-stage-link' );
		$body = aspire_hp_p( sprintf( '%02d', $index + 1 ) . ' / ' . $stage[0], 'hp-stage-number' ) . aspire_hp_h( $stage[1], 3, 'hp-stage-statement' ) . aspire_hp_p( $stage[2], 'hp-stage-client' );
		$body .= aspire_hp_group( aspire_hp_p( 'With Aspire', 'hp-copy-label' ) . aspire_hp_p( $stage[3] ), 'hp-stage-advisor' );
		$body .= aspire_hp_group( aspire_hp_p( 'What you take forward', 'hp-copy-label' ) . aspire_hp_p( $stage[4] ), 'hp-stage-outcome' );
		$body .= aspire_hp_p( $stage[5], 'hp-stage-technology' );
		$stages .= aspire_hp_group( $body, 'hp-stage', $anchor, 'section', $journey[0] . ' — ' . $stage[0] );
	}
	$track = aspire_hp_h( $journey[1], 3, 'hp-journey-service' ) . aspire_hp_group( $nav, 'hp-stage-navigation', '', 'div', 'Journey stages' ) . aspire_hp_group( $stages, 'hp-stage-copy' ) . aspire_hp_link( $journey[2], 'tel:+17139332001', 'hp-journey-advisor hp-link' ) . aspire_hp_journey_artifact( $key ) . aspire_hp_artifact_topics( $key );
	$tracks .= aspire_hp_group( $track, 'hp-journey-track hp-track-' . $key, 'journey-' . $key, 'section', $journey[0] );
}
$service_links = '';
foreach ( array( 'tenant-representation' => array( 'Tenant Representation', 'tenant' ), 'landlord-representation' => array( 'Landlord Representation', 'owner' ), 'investment-sales' => array( 'Investment Sales', 'investor' ), 'investor-developer-services' => array( 'Investor & Developer Services', 'investor' ), 'property-management' => array( 'Property Management', 'management' ), 'cre-consulting' => array( 'CRE Consulting', 'management' ) ) as $slug => $item ) { $service_links .= aspire_hp_group( aspire_hp_link( $item[0], '#journey-' . $item[1] ), 'hp-service-destination', $slug ); }
echo aspire_hp_group( aspire_hp_group( $intro, 'hp-journey-intro hp-shell hp-orientation' ) . aspire_hp_group( aspire_hp_group( $scene, 'hp-journey-scene', '', 'div', 'Transforming property story' ) . aspire_hp_group( $tracks, 'hp-journey-content' ), 'hp-journey-experience' ) . aspire_hp_group( $service_links, 'hp-service-index hp-shell', 'expertise', 'nav', 'Aspire service index' ), 'hp-section hp-journey', 'client-journey', 'section', 'Client journey experience' );
