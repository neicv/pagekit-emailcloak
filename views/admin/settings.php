<?php $view->script('views', 'friendlyit/emailcloak:app/bundle/settings.js', ['vue', 'jquery']);?>

<div id="settings" class="uk-form uk-form-horizontal" v-cloak>

    <form class="uk-form uk-form-horizontal">
	
	<div class="uk-grid pk-grid-large" data-uk-grid-margin>
        <div class="pk-width-sidebar">

            <div class="uk-panel">

                <ul class="uk-nav uk-nav-side pk-nav-large" data-uk-tab="{ connect: '#tab-content' }">
                    <li><a><i class="pk-icon-large-settings uk-margin-right"></i> {{ 'General' | trans }}</a></li>
                </ul>

            </div>

        </div>
        <div class="pk-width-content">

            <ul id="tab-content" class="uk-switcher uk-margin">
                <li>
					<div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
                        <div data-uk-margin>

                            <h2 class="uk-margin-remove">{{ 'Emailcloak' | trans }}</h2>

                        </div>
						<div data-uk-margin>

                            <button class="uk-button uk-button-primary" @click.prevent="save">{{ 'Save' | trans }}</button>

                        </div>

                    </div>
					<hr class="uk-article-divider">

					<div class="uk-form-controls-condensed">
						<div class="uk-form-row">
							<span class="uk-form-label" data-uk-tooltip="{pos:'bottom-right', delay : '500' , animation : 'true'}" title="{{'Cloaks all email addresses in content from spambots using JavaScript.' | trans}}">{{'Select how email addresses will be displayed.' | trans}}</span>
							
							<span class="uk-form-label">{{'Mode' | trans}}</span>
								<div class="uk-form-controls uk-form-controls-text">
									<select name="Pizza" v-model="config.mode">
										<option value="PLG_CONTENT_EMAILCLOAK_NONLINKABLE">{{'Non-linkable Text' | trans}}</option>
										<option value="PLG_CONTENT_EMAILCLOAK_LINKABLE">{{'As linkable mailto address' | trans}}</option>
						
									</select>
								</div>
						</div>
					</div>
                </li>
            </ul>

        </div>
    </div>
    </form>
</div>
