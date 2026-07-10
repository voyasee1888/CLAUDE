<?php defined('ABSPATH') || exit; ?>
<section id="<?php echo esc_attr($uid); ?>" class="vsb7" data-vsb-root data-mode="<?php echo esc_attr($mode); ?>" data-units="<?php echo esc_attr($units); ?>" data-initial-airline="<?php echo esc_attr($initial_airline); ?>" data-saved-token="<?php echo esc_attr($saved_token); ?>">
    <div class="vsb7-live" data-vsb-live aria-live="polite"></div>
    <?php if ($ad_top) : ?><div class="vsb7-ad top"><span><?php echo esc_html__('Advertisement', 'voyasee-bagfit'); ?></span><?php echo $ad_top; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>

    <header class="vsb7-hero">
        <div class="vsb7-hero-copy">
            <div class="vsb7-brand"><b>V</b><span>VOYASEE <em>BAGFIT</em></span></div>
            <span class="vsb7-eyebrow"><?php echo esc_html__('AIRLINE CARRY-ON SIZE CHECKER', 'voyasee-bagfit'); ?></span>
            <h2><?php echo esc_html__('Will your bag fit every flight?', 'voyasee-bagfit'); ?></h2>
            <p><?php echo esc_html__('Measure once, compare every operating airline and see exactly which size, weight or booking condition needs attention.', 'voyasee-bagfit'); ?></p>
            <div class="vsb7-trust">
                <span><i>250</i><?php echo esc_html__('airline profiles', 'voyasee-bagfit'); ?></span>
                <span><i>7,883</i><?php echo esc_html__('airport records', 'voyasee-bagfit'); ?></span>
                <span><i>0</i><?php echo esc_html__('invented fees', 'voyasee-bagfit'); ?></span>
            </div>
            <div class="vsb7-hero-dims">
                <span class="vsb7-hero-dims-label"><?php echo esc_html__('Typical carry-on limit', 'voyasee-bagfit'); ?></span>
                <span><b>55</b><small><?php echo esc_html__('cm H', 'voyasee-bagfit'); ?></small></span>
                <em>×</em>
                <span><b>40</b><small><?php echo esc_html__('cm W', 'voyasee-bagfit'); ?></small></span>
                <em>×</em>
                <span><b>23</b><small><?php echo esc_html__('cm D', 'voyasee-bagfit'); ?></small></span>
            </div>
        </div>
        <div class="vsb7-hero-visual" aria-hidden="true">
            <div class="vsb7-scan-frame"><div class="vsb7-case"><i></i><i></i><i></i></div></div>
        </div>
    </header>

    <nav class="vsb7-mode-tabs" aria-label="<?php echo esc_attr__('Choose a checker mode', 'voyasee-bagfit'); ?>">
        <button type="button" data-vsb-mode="quick"><i>⚡</i><b><?php echo esc_html__('Quick Check', 'voyasee-bagfit'); ?></b><span><?php echo esc_html__('One bag · one airline', 'voyasee-bagfit'); ?></span></button>
        <button type="button" data-vsb-mode="full"><i>✈</i><b><?php echo esc_html__('Full Trip', 'voyasee-bagfit'); ?></b><span><?php echo esc_html__('Multiple bags and flights', 'voyasee-bagfit'); ?></span></button>
        <button type="button" data-vsb-mode="reverse"><i>⌕</i><b><?php echo esc_html__('Which Airlines Fit?', 'voyasee-bagfit'); ?></b><span><?php echo esc_html__('Compare one bag globally', 'voyasee-bagfit'); ?></span></button>
        <button type="button" data-vsb-mode="shared"><i>◇</i><b><?php echo esc_html__('Best Shared Size', 'voyasee-bagfit'); ?></b><span><?php echo esc_html__('One size for chosen airlines', 'voyasee-bagfit'); ?></span></button>
    </nav>

    <div class="vsb7-app-shell">
        <main class="vsb7-main">
            <section data-vsb-planner>
                <div class="vsb7-progress" data-vsb-progress>
                    <button type="button" data-vsb-step-nav="1"><i>1</i><span><b><?php echo esc_html__('Your bags', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Measure outside edges', 'voyasee-bagfit'); ?></small></span></button>
                    <span></span>
                    <button type="button" data-vsb-step-nav="2"><i>2</i><span><b><?php echo esc_html__('Your journey', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Airlines and allowances', 'voyasee-bagfit'); ?></small></span></button>
                    <span></span>
                    <button type="button" data-vsb-step-nav="3"><i>3</i><span><b><?php echo esc_html__('Decision', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Clear infographic result', 'voyasee-bagfit'); ?></small></span></button>
                </div>

                <section class="vsb7-panel" data-vsb-step="1">
                    <div class="vsb7-section-title"><div><span><?php echo esc_html__('STEP 1', 'voyasee-bagfit'); ?></span><h3><?php echo esc_html__('Create your Bag Passport', 'voyasee-bagfit'); ?></h3><p><?php echo esc_html__('Use packed measurements and include wheels, handles, filled pockets and anything protruding.', 'voyasee-bagfit'); ?></p></div><button type="button" class="vsb7-btn soft" data-vsb-add-bag>＋ <?php echo esc_html__('Add another bag', 'voyasee-bagfit'); ?></button></div>
                    <div class="vsb7-bag-grid" data-vsb-bags></div>
                    <div class="vsb7-load-error" hidden><button type="button" class="vsb7-btn secondary" data-vsb-retry-load><?php echo esc_html__('↻ Retry loading airlines', 'voyasee-bagfit'); ?></button></div>
                    <template data-vsb-bag-template>
                        <article class="vsb7-bag-card" data-vsb-bag>
                            <header><div><span data-bag-number><?php echo esc_html__('BAG 1', 'voyasee-bagfit'); ?></span><b data-bag-title><?php echo esc_html__('My bag', 'voyasee-bagfit'); ?></b></div><button type="button" data-bag-remove aria-label="<?php echo esc_attr__('Remove bag', 'voyasee-bagfit'); ?>">×</button></header>
                            <div class="vsb7-bag-layout">
                                <div class="vsb7-bag-form">
                                    <div class="vsb7-type-choice" role="radiogroup" aria-label="<?php echo esc_attr__('Bag type', 'voyasee-bagfit'); ?>">
                                        <button type="button" data-bag-type="personal"><i>▰</i><b><?php echo esc_html__('Personal item', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Under the seat', 'voyasee-bagfit'); ?></small></button>
                                        <button type="button" data-bag-type="cabin"><i>▣</i><b><?php echo esc_html__('Carry-on', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Overhead cabin bag', 'voyasee-bagfit'); ?></small></button>
                                        <button type="button" data-bag-type="checked"><i>▤</i><b><?php echo esc_html__('Checked bag', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Aircraft hold', 'voyasee-bagfit'); ?></small></button>
                                    </div>
                                    <div class="vsb7-fields two">
                                        <label><span><?php echo esc_html__('Bag name', 'voyasee-bagfit'); ?></span><input type="text" maxlength="50" data-bag-field="name" value="My bag"></label>
                                        <label data-full-only><span><?php echo esc_html__('Traveller', 'voyasee-bagfit'); ?></span><select data-bag-field="owner"><option value="1"><?php echo esc_html__('Traveller 1', 'voyasee-bagfit'); ?></option></select></label>
                                    </div>
                                    <div class="vsb7-preset-row"><label><span><?php echo esc_html__('Quick preset', 'voyasee-bagfit'); ?></span><select data-bag-preset><option value=""><?php echo esc_html__('— enter own measurements —', 'voyasee-bagfit'); ?></option><optgroup label="<?php echo esc_attr__('Personal item', 'voyasee-bagfit'); ?>"><option value="40,30,15,cm">40×30×15 cm (standard personal)</option><option value="45,35,20,cm">45×35×20 cm (large personal)</option><option value="18,14,8,in">18×14×8 in (US personal)</option></optgroup><optgroup label="<?php echo esc_attr__('Carry-on bag', 'voyasee-bagfit'); ?>"><option value="55,40,23,cm">55×40×23 cm (EU standard)</option><option value="56,45,25,cm">56×45×25 cm (EU large)</option><option value="55,35,20,cm">55×35×20 cm (low-cost compact)</option><option value="22,14,9,in">22×14×9 in (US domestic)</option><option value="21,13,8,in">21×13×8 in (US compact)</option></optgroup><optgroup label="<?php echo esc_attr__('Checked bag', 'voyasee-bagfit'); ?>"><option value="68,45,27,cm">68×45×27 cm (standard 23 kg)</option><option value="78,52,31,cm">78×52×31 cm (large 32 kg)</option><option value="27,18,12,in">27×18×12 in (US standard)</option></optgroup></select></label></div>
                                    <div class="vsb7-measure-row">
                                        <label><span><?php echo esc_html__('Height (longest side)', 'voyasee-bagfit'); ?></span><input type="number" min="0.1" max="300" step="0.1" data-bag-field="length"><em data-d-unit>cm</em></label>
                                        <label><span><?php echo esc_html__('Width', 'voyasee-bagfit'); ?></span><input type="number" min="0.1" max="300" step="0.1" data-bag-field="width"><em data-d-unit>cm</em></label>
                                        <label><span><?php echo esc_html__('Depth', 'voyasee-bagfit'); ?></span><input type="number" min="0.1" max="300" step="0.1" data-bag-field="height"><em data-d-unit>cm</em></label>
                                        <label><span><?php echo esc_html__('Packed weight', 'voyasee-bagfit'); ?></span><input type="number" min="0" max="100" step="0.1" data-bag-field="weight"><em data-w-unit>kg</em></label>
                                    </div>
                                    <div class="vsb7-fields two compact">
                                        <label><span><?php echo esc_html__('Dimension unit', 'voyasee-bagfit'); ?></span><select data-bag-field="dimension_unit"><option value="cm">cm</option><option value="in">inches</option></select></label>
                                        <label><span><?php echo esc_html__('Weight unit', 'voyasee-bagfit'); ?></span><select data-bag-field="weight_unit"><option value="kg">kg</option><option value="lb">lb</option></select></label>
                                    </div>
                                    <div class="vsb7-checks"><label><input type="checkbox" data-bag-field="wheels_included" checked><span><?php echo esc_html__('Wheels and handles included', 'voyasee-bagfit'); ?></span></label><label><input type="checkbox" data-bag-field="soft_sided"><span><?php echo esc_html__('Soft-sided', 'voyasee-bagfit'); ?></span></label><label><input type="checkbox" data-bag-field="expandable"><span><?php echo esc_html__('Expandable section open', 'voyasee-bagfit'); ?></span></label></div>
                                </div>
                                <aside class="vsb7-bag-passport">
                                    <span><?php echo esc_html__('BAG PASSPORT', 'voyasee-bagfit'); ?></span>
                                    <div class="vsb7-mini-case"><i></i><b data-preview-height>—</b><em data-preview-width>—</em></div>
                                    <dl><div><dt><?php echo esc_html__('Linear size', 'voyasee-bagfit'); ?></dt><dd data-preview-linear>—</dd></div><div><dt><?php echo esc_html__('Volume', 'voyasee-bagfit'); ?></dt><dd data-preview-volume>—</dd></div><div><dt><?php echo esc_html__('Weight', 'voyasee-bagfit'); ?></dt><dd data-preview-weight>—</dd></div></dl>
                                </aside>
                            </div>
                        </article>
                    </template>
                    <div class="vsb7-nav"><span></span><button type="button" class="vsb7-btn primary" data-vsb-next="2"><?php echo esc_html__('Continue to journey', 'voyasee-bagfit'); ?> →</button></div>
                </section>

                <section class="vsb7-panel" data-vsb-step="2" hidden>
                    <div class="vsb7-section-title"><div><span><?php echo esc_html__('STEP 2', 'voyasee-bagfit'); ?></span><h3><?php echo esc_html__('Build your Flight Rule Stack', 'voyasee-bagfit'); ?></h3><p><?php echo esc_html__('Choose the operating airline whenever possible. For checked baggage, use the allowance printed in the issued booking.', 'voyasee-bagfit'); ?></p></div><button type="button" class="vsb7-btn soft" data-vsb-add-flight>＋ <?php echo esc_html__('Add flight', 'voyasee-bagfit'); ?></button></div>
                    <div class="vsb7-journey-settings" data-full-only>
                        <label><span><?php echo esc_html__('Ticket arrangement', 'voyasee-bagfit'); ?></span><select data-journey="ticket_type"><option value="one_ticket"><?php echo esc_html__('All flights on one ticket', 'voyasee-bagfit'); ?></option><option value="separate_tickets"><?php echo esc_html__('Separate tickets', 'voyasee-bagfit'); ?></option><option value="unknown"><?php echo esc_html__('Not sure', 'voyasee-bagfit'); ?></option></select></label>
                        <label><span><?php echo esc_html__('Checked through to final destination?', 'voyasee-bagfit'); ?></span><select data-journey="checked_through"><option value="unknown"><?php echo esc_html__('Not sure', 'voyasee-bagfit'); ?></option><option value="yes"><?php echo esc_html__('Yes', 'voyasee-bagfit'); ?></option><option value="no"><?php echo esc_html__('No', 'voyasee-bagfit'); ?></option></select></label>
                        <label><span><?php echo esc_html__('Travellers', 'voyasee-bagfit'); ?></span><input type="number" min="1" max="9" value="1" data-journey="travellers"></label>
                        <label class="check"><input type="checkbox" data-journey="self_transfer"><span><?php echo esc_html__('This trip includes a self-transfer', 'voyasee-bagfit'); ?></span></label>
                    </div>
                    <div data-vsb-flights></div>
                    <template data-vsb-flight-template>
                        <article class="vsb7-flight-card" data-vsb-flight>
                            <header><div><span data-flight-number><?php echo esc_html__('FLIGHT 1', 'voyasee-bagfit'); ?></span><b data-flight-title><?php echo esc_html__('Choose an airline', 'voyasee-bagfit'); ?></b></div><button type="button" data-flight-remove aria-label="<?php echo esc_attr__('Remove flight', 'voyasee-bagfit'); ?>">×</button></header>
                            <div class="vsb7-fields four">
                                <label><span><?php echo esc_html__('Marketing airline', 'voyasee-bagfit'); ?></span><select data-flight="airline"></select></label>
                                <label data-full-only><span><?php echo esc_html__('Operating airline', 'voyasee-bagfit'); ?></span><select data-flight="operating"></select></label>
                                <label><span><?php echo esc_html__('Cabin', 'voyasee-bagfit'); ?></span><select data-flight="cabin"><option value="unknown"><?php echo esc_html__('Not sure', 'voyasee-bagfit'); ?></option><option value="economy"><?php echo esc_html__('Economy', 'voyasee-bagfit'); ?></option><option value="premium"><?php echo esc_html__('Premium Economy', 'voyasee-bagfit'); ?></option><option value="business"><?php echo esc_html__('Business', 'voyasee-bagfit'); ?></option><option value="first"><?php echo esc_html__('First', 'voyasee-bagfit'); ?></option></select></label>
                                <label><span><?php echo esc_html__('Fare type', 'voyasee-bagfit'); ?></span><select data-flight="fare_class"><option value="unknown"><?php echo esc_html__('Not sure', 'voyasee-bagfit'); ?></option><option value="basic"><?php echo esc_html__('Basic Economy', 'voyasee-bagfit'); ?></option><option value="standard"><?php echo esc_html__('Standard / Main', 'voyasee-bagfit'); ?></option><option value="premium_economy"><?php echo esc_html__('Premium Economy', 'voyasee-bagfit'); ?></option><option value="flexible"><?php echo esc_html__('Flexible / Refundable', 'voyasee-bagfit'); ?></option></select></label>
                            </div>
                            <div class="vsb7-fields three" data-full-only>
                                <label><span><?php echo esc_html__('Origin airport or city', 'voyasee-bagfit'); ?></span><input type="text" data-flight="origin" list="<?php echo esc_attr($uid); ?>-airports" autocomplete="off"></label>
                                <label><span><?php echo esc_html__('Destination airport or city', 'voyasee-bagfit'); ?></span><input type="text" data-flight="destination" list="<?php echo esc_attr($uid); ?>-airports" autocomplete="off"></label>
                                <label><span><?php echo esc_html__('Travel date', 'voyasee-bagfit'); ?></span><input type="date" data-flight="date"></label>
                            </div>
                            <div class="vsb7-allowance-grid" data-flight-allowances></div>
                            <div class="vsb7-ticket-parser" data-ticket-parser hidden>
                                <div><span><?php echo esc_html__('PASTE ONLY THE BAGGAGE LINE', 'voyasee-bagfit'); ?></span><b><?php echo esc_html__('Example: 1PC 23KG or 2PC 23KG EACH', 'voyasee-bagfit'); ?></b></div>
                                <div class="vsb7-parser-row"><input type="text" maxlength="120" data-flight="ticket-text" placeholder="1PC 23KG · 158CM"><button type="button" class="vsb7-btn soft" data-parse-ticket><?php echo esc_html__('Read allowance', 'voyasee-bagfit'); ?></button></div>
                                <div class="vsb7-fields three compact"><label><span><?php echo esc_html__('Weight per bag', 'voyasee-bagfit'); ?></span><input type="number" min="0" max="100" step="0.1" data-flight="ticket-weight" placeholder="23"></label><label><span><?php echo esc_html__('Total dimensions', 'voyasee-bagfit'); ?></span><input type="number" min="0" max="400" step="0.1" data-flight="ticket-linear" placeholder="158"></label><label><span><?php echo esc_html__('Included pieces', 'voyasee-bagfit'); ?></span><input type="number" min="0" max="10" value="1" data-flight="ticket-pieces"></label></div>
                                <small><?php echo esc_html__('Review every extracted value before checking. Do not paste names, booking references or ticket numbers.', 'voyasee-bagfit'); ?></small>
                            </div>
                            <label class="vsb7-inline-check" data-full-only><input type="checkbox" data-flight="codeshare"><span><?php echo esc_html__('I cannot confirm the operating airline', 'voyasee-bagfit'); ?></span></label>
                            <footer><span data-flight-source><?php echo esc_html__('Choose an airline to view the official source.', 'voyasee-bagfit'); ?></span><b data-flight-coverage>—</b></footer>
                        </article>
                    </template>
                    <datalist id="<?php echo esc_attr($uid); ?>-airports" data-vsb-airport-list></datalist>
                    <div class="vsb7-nav"><button type="button" class="vsb7-btn secondary" data-vsb-back="1">← <?php echo esc_html__('Back', 'voyasee-bagfit'); ?></button><button type="button" class="vsb7-btn primary" data-vsb-check><?php echo esc_html__('Check every bag', 'voyasee-bagfit'); ?> →</button></div>
                </section>

                <section class="vsb7-panel result" data-vsb-step="3" hidden>
                    <div class="vsb7-section-title"><div><span><?php echo esc_html__('YOUR DECISION', 'voyasee-bagfit'); ?></span><h3><?php echo esc_html__('BagFit Flight Decision Board', 'voyasee-bagfit'); ?></h3><p><?php echo esc_html__('A direct answer, the limiting bag or flight, and the most useful next action.', 'voyasee-bagfit'); ?></p></div></div>
                    <div class="vsb7-loading" data-vsb-loading hidden><i></i><b><?php echo esc_html__('Comparing every bag with every flight…', 'voyasee-bagfit'); ?></b></div>
                    <div data-vsb-result></div>
                    <?php if ($ad_result) : ?><div class="vsb7-ad result-ad" data-vsb-result-ad hidden><span><?php echo esc_html__('Advertisement', 'voyasee-bagfit'); ?></span><?php echo $ad_result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
                    <div data-vsb-context-links></div>
                    <div class="vsb7-result-actions" data-vsb-actions hidden><button type="button" class="vsb7-btn secondary" data-vsb-restart>↻ <?php echo esc_html__('New check', 'voyasee-bagfit'); ?></button><button type="button" class="vsb7-btn soft" data-vsb-save>🔗 <?php echo esc_html__('Save & copy link', 'voyasee-bagfit'); ?></button><button type="button" class="vsb7-btn primary" data-vsb-pdf>⇩ <?php echo esc_html__('Open report', 'voyasee-bagfit'); ?></button></div>
                    <div class="vsb7-share" data-vsb-share-output hidden></div>
                </section>
            </section>

            <section class="vsb7-special" data-vsb-reverse hidden>
                <div class="vsb7-section-title"><div><span><?php echo esc_html__('REVERSE BAG SEARCH', 'voyasee-bagfit'); ?></span><h3><?php echo esc_html__('Which airlines accept this bag?', 'voyasee-bagfit'); ?></h3><p><?php echo esc_html__('Enter one personal item or carry-on and compare it with all stored airline rules.', 'voyasee-bagfit'); ?></p></div></div>
                <div class="vsb7-special-grid"><div data-reverse-bag></div><aside class="vsb7-filter-card"><label><span><?php echo esc_html__('Bag category', 'voyasee-bagfit'); ?></span><select data-reverse="type"><option value="personal"><?php echo esc_html__('Personal item', 'voyasee-bagfit'); ?></option><option value="cabin"><?php echo esc_html__('Carry-on bag', 'voyasee-bagfit'); ?></option></select></label><label class="vsb7-inline-check"><input type="checkbox" data-reverse="free_only" checked><span><?php echo esc_html__('Show free/included allowance matches first', 'voyasee-bagfit'); ?></span></label><button type="button" class="vsb7-btn primary" data-reverse-submit><?php echo esc_html__('Compare across airlines', 'voyasee-bagfit'); ?> →</button></aside></div>
                <div data-reverse-result></div>
            </section>

            <section class="vsb7-special" data-vsb-shared hidden>
                <div class="vsb7-section-title"><div><span><?php echo esc_html__('BEST SHARED BAG SIZE', 'voyasee-bagfit'); ?></span><h3><?php echo esc_html__('Find one bag size for all selected airlines', 'voyasee-bagfit'); ?></h3><p><?php echo esc_html__('Choose two or more airlines. BagFit calculates the smallest shared recorded dimensions and weight.', 'voyasee-bagfit'); ?></p></div></div>
                <div class="vsb7-shared-builder"><div><label><span><?php echo esc_html__('Search airlines', 'voyasee-bagfit'); ?></span><input type="search" data-shared-search placeholder="Ryanair, easyJet, Lufthansa…"></label><div class="vsb7-airline-picks" data-shared-picks></div></div><aside><label><span><?php echo esc_html__('Bag category', 'voyasee-bagfit'); ?></span><select data-shared="type"><option value="personal"><?php echo esc_html__('Personal item', 'voyasee-bagfit'); ?></option><option value="cabin" selected><?php echo esc_html__('Carry-on bag', 'voyasee-bagfit'); ?></option></select></label><label class="vsb7-inline-check"><input type="checkbox" data-shared="free_only"><span><?php echo esc_html__('Included/free allowance only', 'voyasee-bagfit'); ?></span></label><div class="vsb7-selected-count"><b data-shared-count>0</b><span><?php echo esc_html__('airlines selected', 'voyasee-bagfit'); ?></span></div><button type="button" class="vsb7-btn primary" data-shared-submit><?php echo esc_html__('Calculate shared size', 'voyasee-bagfit'); ?> →</button></aside></div>
                <div data-shared-result></div>
            </section>
        </main>

        <aside class="vsb7-summary" data-vsb-summary>
            <span><?php echo esc_html__('LIVE TRIP SUMMARY', 'voyasee-bagfit'); ?></span>
            <div><i>▣</i><p><b data-summary-bags>1 bag</b><small data-summary-bag-detail><?php echo esc_html__('Waiting for measurements', 'voyasee-bagfit'); ?></small></p></div>
            <div><i>✈</i><p><b data-summary-flights>1 flight</b><small data-summary-airline><?php echo esc_html__('Choose an airline', 'voyasee-bagfit'); ?></small></p></div>
            <div><i>✓</i><p><b><?php echo esc_html__('Official confirmation', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Every result links back to the airline source.', 'voyasee-bagfit'); ?></small></p></div>
        </aside>
    </div>

    <footer class="vsb7-footer">
        <div class="vsb7-footer-top"><div><span>VOYASEE BAGFIT</span><h4><?php echo esc_html__('Continue from baggage planning to a travel-ready trip.', 'voyasee-bagfit'); ?></h4><p><?php echo esc_html__('Voyasee tools come first. Optional partner links appear only for a separate booking need.', 'voyasee-bagfit'); ?></p></div><a href="https://voyasee.com/travel-tools/" target="_blank" rel="noopener"><?php echo esc_html__('Explore all travel tools', 'voyasee-bagfit'); ?> →</a></div>
        <div class="vsb7-footer-columns">
            <div class="vsb7-footer-col">
                <h5><?php echo esc_html__('Prepare your trip', 'voyasee-bagfit'); ?></h5>
                <div class="vsb7-footer-tools">
                    <a data-tool="packing" href="#"><i>▣</i><span><b><?php echo esc_html__('Packing List', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Pack lighter for this allowance', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="medicine" href="#"><i>✚</i><span><b><?php echo esc_html__('Medicine Checker', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Check cabin and restricted items', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="transit" href="#"><i>⇄</i><span><b><?php echo esc_html__('Transit Risk', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Review recheck and self-transfer risk', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="passport" href="#"><i>✓</i><span><b><?php echo esc_html__('Travel Passport', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Complete your pre-flight readiness', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="budget" href="#"><i>€</i><span><b><?php echo esc_html__('Trip Budget', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Add baggage and hidden costs', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="printables" href="#"><i>⇩</i><span><b><?php echo esc_html__('Travel Printables', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Keep an offline travel checklist', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="hub" href="#"><i>◎</i><span><b><?php echo esc_html__('Smart Travel Hub', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Safety, weather and advisories', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="month" href="#"><i>▤</i><span><b><?php echo esc_html__('Best Time to Visit', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Plan around the right season', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="compare" href="#"><i>⇌</i><span><b><?php echo esc_html__('Compare Destinations', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Weigh two trips side by side', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="quiz" href="#"><i>❖</i><span><b><?php echo esc_html__('Destination Quiz', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Find your next destination', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="map" href="#"><i>⌖</i><span><b><?php echo esc_html__('Interactive Travel Map', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Explore destinations visually', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="scam" href="#"><i>⚑</i><span><b><?php echo esc_html__('Scam Shield', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Spot common travel scams', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="flights" href="#"><i>✈</i><span><b><?php echo esc_html__('Book Flights', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Compare and book your route', 'voyasee-bagfit'); ?></small></span></a>
                    <a data-tool="tours" href="#"><i>◈</i><span><b><?php echo esc_html__('Book Tours', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Activities at your destination', 'voyasee-bagfit'); ?></small></span></a>
                </div>
            </div>
            <div class="vsb7-footer-col">
                <h5><?php echo esc_html__('Optional booking resources', 'voyasee-bagfit'); ?></h5>
                <div class="vsb7-footer-partners">
                    <a data-affiliate="flights" href="#"><b><?php echo esc_html__('Compare flights', 'voyasee-bagfit'); ?></b><small>Aviasales</small></a>
                    <a data-affiliate="kiwi" href="#"><b><?php echo esc_html__('Search flights (alt.)', 'voyasee-bagfit'); ?></b><small>Kiwi.com</small></a>
                    <a data-affiliate="booking" href="#"><b><?php echo esc_html__('Find accommodation', 'voyasee-bagfit'); ?></b><small>Booking.com</small></a>
                    <a data-affiliate="bookingApac" href="#"><b><?php echo esc_html__('Stay — Asia/Pacific & Middle East', 'voyasee-bagfit'); ?></b><small>Booking.com</small></a>
                    <a data-affiliate="storage" href="#"><b><?php echo esc_html__('Store luggage', 'voyasee-bagfit'); ?></b><small>Radical Storage</small></a>
                    <a data-affiliate="transfer" href="#"><b><?php echo esc_html__('Airport transfer', 'voyasee-bagfit'); ?></b><small>Kiwitaxi</small></a>
                    <a data-affiliate="insurance" href="#"><b><?php echo esc_html__('Review travel coverage', 'voyasee-bagfit'); ?></b><small>SafetyWing</small></a>
                    <a data-affiliate="compensation" href="#"><b><?php echo esc_html__('Flight disruption help', 'voyasee-bagfit'); ?></b><small>Compensair</small></a>
                    <a data-affiliate="visa" href="#"><b><?php echo esc_html__('Check visa requirements', 'voyasee-bagfit'); ?></b><small>VisaHQ</small></a>
                    <a data-affiliate="esim" href="#"><b><?php echo esc_html__('Get a travel eSIM', 'voyasee-bagfit'); ?></b><small>Yesim</small></a>
                    <a data-affiliate="asiaTransport" href="#"><b><?php echo esc_html__('Buses, trains & ferries', 'voyasee-bagfit'); ?></b><small>12Go Asia</small></a>
                    <a data-affiliate="activities" href="#"><b><?php echo esc_html__('Book tours & activities', 'voyasee-bagfit'); ?></b><small>Klook</small></a>
                    <a data-affiliate="malaysiaAirlines" href="#"><b><?php echo esc_html__('Fly Malaysia Airlines', 'voyasee-bagfit'); ?></b><small>Malaysia Airlines</small></a>
                    <a data-affiliate="luggage" href="#"><b><?php echo esc_html__('Shop travel luggage', 'voyasee-bagfit'); ?></b><small><?php echo esc_html__('Luggage retailer', 'voyasee-bagfit'); ?></small></a>
                </div>
                <p class="vsb7-sponsored"><?php echo esc_html__('Sponsored links are separate from the baggage result and never influence it.', 'voyasee-bagfit'); ?></p>
            </div>
        </div>
        <?php if ($ad_footer) : ?><div class="vsb7-ad footer"><span><?php echo esc_html__('Advertisement', 'voyasee-bagfit'); ?></span><?php echo $ad_footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
        <div class="vsb7-footer-bottom"><div><b>VOYASEE.COM</b><span><?php echo esc_html__('Plan clearly · Verify officially · Travel better', 'voyasee-bagfit'); ?></span></div><nav><a href="https://voyasee.com/privacy-policy/" target="_blank" rel="noopener"><?php echo esc_html__('Privacy', 'voyasee-bagfit'); ?></a><a href="https://voyasee.com/affiliate-disclosure/" target="_blank" rel="noopener"><?php echo esc_html__('Affiliate disclosure', 'voyasee-bagfit'); ?></a><a href="https://voyasee.com/contact-us/" target="_blank" rel="noopener"><?php echo esc_html__('Contact', 'voyasee-bagfit'); ?></a></nav><small><?php echo esc_html__('Data', 'voyasee-bagfit'); ?> <i data-vsb-data-version></i></small></div>
    </footer>
</section>
