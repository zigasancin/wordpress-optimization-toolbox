<div template-id="wpfc-modal-preload" style="display:none; top: 10.5px; left: 226px; position: absolute; padding: 6px; height: auto; width: 560px; z-index: 10001;">
	
	<style type="text/css">
		div[wpfc-cdn-page="sitemap"] .wpfc-textbox-con{position:absolute;left:0;top:0;-webkit-border-radius:3px;-moz-border-radius:3px;background:#fff;-webkit-box-shadow:0 2px 6px 2px rgba(0,0,0,0.3);box-shadow:0 2px 6px 2px rgba(0,0,0,0.3);-moz-box-shadow:0 2px 6px 2px rgba(0,0,0,0.3);float:left;z-index:444;width:150px;border:1px solid #adadad;}
		div[wpfc-cdn-page="sitemap"] .keyword-item-list:after{box-shodow:0 2px 6px 0 rgba(0, 0, 0, 0.15);content:'';clear:both;height:0;visibility:hidden;display:block}
		div[wpfc-cdn-page="sitemap"] .fixed-search input{width:100%;padding:6px 9px;line-height:20px;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;box-sizing:border-box;margin:0;border:none;border-bottom:1px solid #ccc;-webkit-box-shadow:0 2px 6px 0 rgba(0,0,0,0.1);box-shadow:0 2px 6px 0 rgba(0,0,0,0.1);-moz-box-shadow:0 2px 6px 0 rgba(0,0,0,0.1);-webkit-border-radius:3px 3px 0 0;-moz-border-radius:3px 3px 0 0;border-radius:3px 3px 0 0;font-weight:bold}.fixed-search input:focus{outline:0}
		div[wpfc-cdn-page="sitemap"] .keyword-item{width:auto;float:left;line-height:22px;position:relative;background:rgba(0,0,0,0.15);margin:0 5px 5px 0;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px;}
		div[wpfc-cdn-page="sitemap"] .wpfc-add-new-keyword, div[wpfc-cdn-page="sitemap"] .keyword-item a.keyword-label{
			background-color: #ffa100;
			color:#ffffff;
			text-decoration:none;
			padding:7px 15px;
			display:block;
			text-shadow:none;
			-webkit-transition:all .1s linear;
			-moz-transition:all .1s linear;
			-o-transition:all .1s linear;
			transition:all .1s linear;
			cursor: pointer;
		}
		div[wpfc-cdn-page="sitemap"] .keyword-item a.keyword-label:hover{
			padding-left: 4px;
			padding-right: 26px;
		}
		div[wpfc-cdn-page="sitemap"] .keyword-item a.keyword-label:hover:after{
			width:16px;
			height:16px;
			background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAAAnNCSVQICFXsRgQAAAAJcEhZcwAAAIAAAACAAc9WmjcAAAAZdEVYdFNvZnR3YXJlAHd3dy5pbmtzY2FwZS5vcmeb7jwaAAAA4klEQVQokWXOMSuFcRQH4EcZZFB3w0BZfIC7mLl3MaGu2cAtqyLJwMQiRuObidFG4hNQJrlFJBlMFgzKMfi/vPe9/ZbT+T11jvAbq2nI8k3aB1MymStPTrTcyWTmi2BDdCQrgprtjjQKIJgw15aZth+Co9KB6zLYL4GLMthqq7/slcFKoVzTa1ClHTT/wKwxt0I4N/wPGqk+NuTNqQ/PLt3oyUEtgQWbQl3NqHVhOgfVBCYdCC3dhnwKSzkYSWDXslDVNG5RqOegksCDPg/ufXv34kxXAkF/SpcBh1492tEbwg+6YscxiN7TegAAAABJRU5ErkJggg==');
			content:"";
			position:absolute;
			top:10px;
			right:4px;
		}

		div[wpfc-cdn-page="sitemap"] .wpfc-add-new-keyword{
			cursor:pointer;
			text-decoration:none;
			background-color:#fff !important;
			color:#ccc !important;
			padding:5px 12px !important;
			border:2px dashed #ccc;
			line-height:21px;
		}
		div[wpfc-cdn-page="sitemap"] .wpfc-add-new-keyword:before{display:inline-block;content:"+";margin:-1px 4px 0 -6px;}
		div[wpfc-cdn-page="sitemap"] .wpfc-add-new-keyword:hover{color:#589b43 !important;border-color:#589b43;}
		
	</style>

	<div style="height: 100%; width: 100%; background: none repeat scroll 0% 0% rgb(0, 0, 0); position: absolute; top: 0px; left: 0px; z-index: -1; opacity: 0.5; border-radius: 8px;">
	</div>
	<div style="z-index: 600; border-radius: 3px;">
		<div style="font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;font-size:12px;background: none repeat scroll 0px 0px rgb(255, 161, 0); z-index: 1000; position: relative; padding: 2px; border-bottom: 1px solid rgb(194, 122, 0); height: 35px; border-radius: 3px 3px 0px 0px;">
			<table width="100%" height="100%">
				<tbody>
					<tr>
						<td valign="middle" style="vertical-align: middle; font-weight: bold; color: rgb(255, 255, 255); text-shadow: 0px 1px 1px rgba(0, 0, 0, 0.5); padding-left: 10px; font-size: 13px; cursor: move;"><?php _e('Preload Settings', 'wp-fastest-cache'); ?></td>
						<td width="20" align="center" style="vertical-align: middle;"></td>
						<td width="20" align="center" style="vertical-align: middle; font-family: Arial,Helvetica,sans-serif; color: rgb(170, 170, 170); cursor: default;">
							<div title="Close Window" class="close-wiz"></div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="window-content-wrapper" style="padding: 8px;">
			<div style="z-index: 1000; height: auto; position: relative; display: inline-block; width: 100%;" class="window-content">


				<div class="wpfc-cdn-pages-container">
					<div wpfc-cdn-page="start" class="wiz-cont">

						<h1><?php _e('Choose a Method', 'wp-fastest-cache'); ?></h1>		
						<p><?php _e('You can specify the method you want the preload feature to use through this section.', 'wp-fastest-cache'); ?></p>
						<div class="wiz-input-cont">
							<select id="wpFastestCachePreload_method" style="width: 100%; max-width: unset;">
										<option value="default">Default&nbsp;&nbsp;(Contents from Newest to Oldest)</option>
										<option value="sitemap">Sitemap</option>
								</select>
					    	<label class="wiz-error-msg"></label>
					    </div>

					</div>


					<div wpfc-cdn-page="default" class="wiz-cont" style="display: none;">
						<h1><?php _e('Content Types', 'wp-fastest-cache'); ?></h1>		
						<p><?php _e('You can specify the contents to be used for preloading and you can sort them as well.', 'wp-fastest-cache'); ?></p>

						<label class="wiz-error-msg" style="padding-left: 0;"></label>

						<div class="preload_sortable_area">

							<style type="text/css">
								.preload_sortable_area div {
									cursor: move;

								}
							</style>

							<div class="wiz-input-cont" style="" data-type="homepage">
								<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_homepage; ?> id="wpFastestCachePreload_homepage" name="wpFastestCachePreload_homepage"></label>
								<label for="wpFastestCachePreload_homepage"><?php _e('Homepage'); ?></label>
							</div>
							<div class="wiz-input-cont" style="" data-type="post">
								<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_post; ?> id="wpFastestCachePreload_post" name="wpFastestCachePreload_post"></label>
								<label for="wpFastestCachePreload_post"><?php _e('Posts'); ?></label>
							</div>
							<div class="wiz-input-cont" style="" data-type="category">
								<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_category; ?> id="wpFastestCachePreload_category" name="wpFastestCachePreload_category"></label>
								<label for="wpFastestCachePreload_category"><?php _e('Categories'); ?></label>
							</div>
							<div class="wiz-input-cont" style="" data-type="page">
								<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_page; ?> id="wpFastestCachePreload_page" name="wpFastestCachePreload_page"></label>
								<label for="wpFastestCachePreload_page"><?php _e('Pages'); ?></label>
							</div>
							<div class="wiz-input-cont" style="" data-type="tag">
								<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_tag; ?> id="wpFastestCachePreload_tag" name="wpFastestCachePreload_tag"></label>
								<label for="wpFastestCachePreload_tag"><?php _e('Tags'); ?></label>
							</div>
							<div class="wiz-input-cont" style="" data-type="attachment">
								<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_attachment; ?> id="wpFastestCachePreload_attachment" name="wpFastestCachePreload_attachment"></label>
								<label for="wpFastestCachePreload_attachment"><?php _e('Attachments', 'wp-fastest-cache'); ?></label>
							</div>

							<div class="wiz-input-cont custom-half" style="" data-type="customposttypes">
								<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_customposttypes; ?> id="wpFastestCachePreload_customposttypes" name="wpFastestCachePreload_customposttypes"></label>
								<label for="wpFastestCachePreload_customposttypes"><?php _e('Custom Post Types', 'wp-fastest-cache'); ?></label>
							</div>

							<div class="wiz-input-cont custom-half" style="" data-type="customTaxonomies">
								<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_customTaxonomies; ?> id="wpFastestCachePreload_customTaxonomies" name="wpFastestCachePreload_customTaxonomies"></label>
								<label for="wpFastestCachePreload_customposttypes"><?php _e('Custom Taxonomies', 'wp-fastest-cache'); ?></label>
							</div>
							


						</div>


						<input type="hidden" value="<?php echo $wpFastestCachePreload_order; ?>" id="wpFastestCachePreload_order" name="wpFastestCachePreload_order">



					</div>



					<div wpfc-cdn-page="sitemap" class="wiz-cont" style="display: none;">

						<h1><?php _e('Sitemaps', 'wp-fastest-cache'); ?></h1>		
						<p><?php _e('You can specify sitemaps to be used for preloading.', 'wp-fastest-cache'); ?></p>


						<div class="wiz-input-cont" style="padding:8px 18px;border-radius:0;text-align:center; background: #fff none repeat scroll 0 0;">
							
							<style type="text/css">
								div[wpfc-cdn-page="sitemap"] .keyword-item {
									width: 100%;
								}

								div[wpfc-cdn-page="sitemap"] .keyword-item .wpfc-textbox-con {
									width: 100%;
								}
							</style>
							
							<label class="wiz-error-msg" style="padding-left: 0;"></label>

							<ul class="keyword-item-list">
						        <li class="keyword-item">
						            <a class="wpfc-add-new-keyword">Add Sitemap URL</a>
						            <div class="wpfc-textbox-con" style="display:none;">
						                <div class="fixed-search"><input type="text" placeholder="Add Sitemap URL"></div>
						            </div>
						        </li>
						    </ul>
						</div>

						<input type="hidden" value="<?php echo $wpFastestCachePreload_sitemap; ?>" id="wpFastestCachePreload_sitemap" name="wpFastestCachePreload_sitemap">

					</div>



					<div wpfc-cdn-page="details" class="wiz-cont" style="display: none;">

						<h1><?php _e('Advanced Settings', 'wp-fastest-cache'); ?></h1>		
						<p><?php _e('You can customize the advanced settings through this section.', 'wp-fastest-cache'); ?></p>

						<div class="wiz-input-cont">
							<label class="mc-input-label" style="display: inline-block;">
								<table id="wpfc-form-spinner-preload" class="wpfc-form-spinner" cellpadding="0" cellspacing="0" border="0" height="20" width="70" style="border: 1px solid rgb(204, 204, 204); border-collapse: collapse; background: rgb(255, 255, 255);">
									<tbody>
										<tr>
											<td class="wpfc-form-spinner-input-td" rowspan="2" style="padding-right: 2px;height: 100%;">
												<div class="wpfc-form-spinner-number" style="height: 100%; width: 100%; border: none; padding: 0px; font-size: 14px; text-align: center; outline: none;padding-top:7px;"><?php echo $wpFastestCachePreload_number; ?></div>
												<input type="hidden" class="wpfc-form-spinner-input" name="wpFastestCachePreload_number" value="<?php echo $wpFastestCachePreload_number; ?>" />
											</td>
											<td class="wpfc-form-spinner-up" style="height: 15px; cursor: default; text-align: center; width: 12px; font-size: 9px; padding-left: 4px; padding-right: 8px; border: 1px solid rgb(204, 204, 204); background: rgb(245, 245, 245);">
												<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAAAFCAQAAAAjkz5TAAAACXBIWXMAAAsTAAALEwEAmpwYAAAABGdBTUEAANjr9RwUqgAAACBjSFJNAABtmAAAc44AAPfgAACDwQAAbsQAAOKFAAAxZAAAGGNUHM53AAAAVklEQVR42gTBIQqAMACG0f8YSwPvZRIMxtVlESwWk8EyUJSFIR7Eg1h1xs/3hNBp4h0sQkLJrF9kzL1FiiY8GwcTPrtC87uQ2Al01JcGWjyOhooy/wMANWktnmvt+MQAAAAASUVORK5CYII=" align="right">
											</td>
										</tr>
										<tr>
											<td class="wpfc-form-spinner-down" style="height: 15px; cursor: default; text-align: center; width: 12px; font-size: 9px; padding-left: 4px; padding-right: 8px; border: 1px solid rgb(204, 204, 204); background: rgb(245, 245, 245);">
												<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAAAFCAQAAAAjkz5TAAAACXBIWXMAAAsTAAALEwEAmpwYAAAABGdBTUEAANjr9RwUqgAAACBjSFJNAABtmAAAc44AAPfgAACDwQAAbsQAAOKFAAAxZAAAGGNUHM53AAAAWElEQVR42gBLALT/AMD/uv+z/6v/ov+Y/472ALf1uv+//7n/s/+c/3/PAKsYovSo/7H/mf969nEhAP8AkxiJ95T/dvZtHv8AAP8A/wB6GHHraR7/AP8AAwCoUy51Bie9nwAAAABJRU5ErkJggg==" align="right">
											</td>
										</tr>
									</tbody>
								</table>
							</label>
							<label style="margin-left:8px;padding-top:4px;"><?php _e('pages per minute', 'wp-fastest-cache'); ?></label>
						</div>

						<div class="wiz-input-cont" style="margin-top: 10px;">
							<label class="mc-input-label" style="margin-right: 5px;"><input type="checkbox" <?php echo $wpFastestCachePreload_restart; ?> id="wpFastestCachePreload_restart" name="wpFastestCachePreload_restart"></label>
							<label for="wpFastestCachePreload_restart"><?php _e('Restart After Completed', 'wp-fastest-cache'); ?></label>
							<a style="margin-left:5px;" target="_blank" href="http://www.wpfastestcache.com/features/restart-preload-after-completed/"><img src="<?php echo plugins_url("wp-fastest-cache/images/info.png"); ?>"></a>
						</div>

					</div>


					<img style="border-radius:100px;" class="wiz-bg-img" src="<?php echo plugins_url("wp-fastest-cache/images/fast-foward.png"); ?>"/>

				</div>
			</div>
		</div>
		<?php include WPFC_MAIN_PATH."templates/buttons.html"; ?>
	</div>
</div>

<script type="text/javascript">
	var WPFC_SPINNER = {
		id: false,
		number: false,
		init: function(id, number){
			this.id = id;
			//this.number = number;
			this.set_number();
			this.click_event();
		},
		set_number: function(){
			this.number = jQuery("#" + this.id + " input.wpfc-form-spinner-input").val();
			this.number = parseInt(this.number);
		},
		click_event: function(){
			var id = this.id;
			var number = this.number;

			jQuery("#" + this.id + " .wpfc-form-spinner-up, #" + this.id + " .wpfc-form-spinner-down").click(function(e){
				if(jQuery(this).attr('class').match(/up$/)){
					number = number + 2;
				}else if(jQuery(this).attr('class').match(/down$/)){
					number = number - 2;
				}

				number = number < 2 ? 2 : number;
				number = number > 12 ? 12 : number;

				jQuery("#" + id + " .wpfc-form-spinner-number").text(number);
				jQuery("#" + id + " input.wpfc-form-spinner-input").val(number);
			});
		}
	};
</script>
<script type="text/javascript">
	var WpFcPreload = {
		pre_id: "wpfc-modal-preload",
		method: "",
		init: function(){
			this.click_event_for_checkbox();
		},
		get_modal_id: function(e){
			let self = this;

			let modal = jQuery(e).closest("div[id^='" + self.pre_id + "']");
			let modal_id = modal.attr("id");

			return modal_id;
		},
		update_style: function(modal_id){
			jQuery("#" + modal_id).find("div[wpfc-cdn-page='default'] div.preload_sortable_area > div").each(function(i, div){

				jQuery(div).removeAttr("style");
				jQuery(div).css({"float" : "left", "width" : "26%", "height" : "35px"});

				if((i+2)%3 == 0){
					jQuery(div).css({"margin-left" : "10px", "margin-right" : "10px"});
				}

				if(i > 2 && i < 6){
					jQuery(div).css({"margin-top" : "10px"});
				}else if(i >= 6){
					jQuery(div).css({"margin-top" : "10px", "margin-bottom" : "23px"});
				}
				
			});
		},
		insert_sitemap_urls: function(modal_id){
			let sitemap = jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content input[name='wpFastestCachePreload_sitemap']").val().trim();

			if(sitemap.length > 0){
				Wpfc_New_Dialog.insert_keywords(modal_id, sitemap);

				jQuery("#" + modal_id).find("select#wpFastestCachePreload_method").val("sitemap");
			}

		},
		sort: function(){
			var self = this;

			let order_string = jQuery("#wpFastestCachePreload_order").val();
			let order_arr = [];
			let clone_div;

			if(order_string.length > 0){
				order_arr = order_string.split(",");

				jQuery.each(order_arr, function(i, value){
					
					jQuery("div[id^='wpfc-modal-preload-'] div.window-content div[wpfc-cdn-page='default'] div.preload_sortable_area > div").each(function(i, div){
						if(jQuery(div).attr("data-type") == value){
							clone_div = jQuery(div).clone();

							div.remove();

							jQuery("div[id^='wpfc-modal-preload-'] div.window-content div[wpfc-cdn-page='default'] div.preload_sortable_area").append(clone_div);
						}
					});
				});
			}

			self.update_style();
		},
		open_modal: function(item){
			let self = this;

			Wpfc_New_Dialog.dialog(self.pre_id, {
				close: function(e){
				},
				finish: function(){

					if(Wpfc_New_Dialog.clone.find("input[name='wpFastestCachePreload_restart']").prop("checked")){
						jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content input[name='wpFastestCachePreload_restart']").prop("checked", true);
					}else{
						jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content input[name='wpFastestCachePreload_restart']").prop("checked", false);
					}

					if(self.method == "default"){
						let order_arr = [];

						Wpfc_New_Dialog.clone.find("div.window-content div.preload_sortable_area input[name]").each(function(){
							if(jQuery(this).is(':checked')){
								jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content div.preload_sortable_area input[name='" + jQuery(this).attr("name") + "']").attr("checked", true);
							}else{
								jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content div.preload_sortable_area input[name='" + jQuery(this).attr("name") + "']").attr("checked", false);
							}

							order_arr.push(jQuery(this).attr("name").replace(/wpFastestCachePreload_/, ""));
						});

						jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content input[name='wpFastestCachePreload_sitemap']").val("");

						jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content input[name='wpFastestCachePreload_order']").val(order_arr.join(","));
						
						Wpfc_New_Dialog.clone.remove();
					}else if(self.method == "sitemap"){
						let urls = [];
						let sitemaps = Wpfc_New_Dialog.clone.find("div[wpfc-cdn-page='sitemap'] ul.keyword-item-list li.keyword-item a.keyword-label");
						
						if(sitemaps.length > 0){
							Wpfc_New_Dialog.clone.find("div[wpfc-cdn-page='sitemap'] label.wiz-error-msg").text("");

						}else{
							Wpfc_New_Dialog.clone.find("div[wpfc-cdn-page='sitemap'] label.wiz-error-msg").text("<?php _e('At least one sitemap must be added', 'wp-fastest-cache'); ?>");

						}

						console.log(sitemaps);

						jQuery(sitemaps).each(function(i, e){
							console.log(jQuery(e).text());
							urls.push(jQuery(e).text());
						});

						if(urls.length > 0){
							jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content div.preload_sortable_area input").prop( "checked", false);

							jQuery("div.tab1 div[template-id='wpfc-modal-preload'] div.window-content input[name='wpFastestCachePreload_sitemap']").val(urls.join(","));
						}


						Wpfc_New_Dialog.clone.remove();
					}



				},
				back: function(e){
					let modal_id = self.get_modal_id(e);
					let current_page = jQuery("#" + modal_id).find(".wpfc-cdn-pages-container div.wiz-cont:visible").attr("wpfc-cdn-page");

					if(current_page == "default" || current_page == "sitemap"){
						Wpfc_New_Dialog.show_page("start");

						Wpfc_New_Dialog.show_button("next");
						Wpfc_New_Dialog.hide_button("back");

					}else if(current_page == "details"){

						if(self.method == "default"){
							Wpfc_New_Dialog.show_page("default");
							self.update_style(modal_id);
						}

						if(self.method == "sitemap"){
							Wpfc_New_Dialog.show_page("sitemap");
						}

						Wpfc_New_Dialog.show_button("next");
						Wpfc_New_Dialog.show_button("back");
						Wpfc_New_Dialog.hide_button("finish");

					}
				},
				next: function(e){
					let modal_id = self.get_modal_id(e);
					let current_page = jQuery("#" + modal_id).find(".wpfc-cdn-pages-container div.wiz-cont:visible").attr("wpfc-cdn-page");

					if(current_page == "start"){
						self.method = jQuery("#" + modal_id).find("select#wpFastestCachePreload_method").val();

						if(self.method == "default"){
							Wpfc_New_Dialog.show_page("default");
							self.update_style(modal_id);
						}

						if(self.method == "sitemap"){
							Wpfc_New_Dialog.show_page("sitemap");
						}


						Wpfc_New_Dialog.show_button("next");
						Wpfc_New_Dialog.show_button("back");
					}else if(current_page == "default"){

						if(Wpfc_New_Dialog.clone.find("div.window-content div.preload_sortable_area input[name]:checked").length > 0){
							Wpfc_New_Dialog.clone.find("div[wpfc-cdn-page='sitemap'] label.wiz-error-msg").text("");

							Wpfc_New_Dialog.show_page("details");

							Wpfc_New_Dialog.hide_button("next");

							Wpfc_New_Dialog.show_button("back");
							Wpfc_New_Dialog.show_button("finish");

						}else{
							Wpfc_New_Dialog.clone.find("div[wpfc-cdn-page='default'] label.wiz-error-msg").text("<?php _e('At least one sitemap must be added', 'wp-fastest-cache'); ?>");
						}

					}else if(current_page == "sitemap"){
						let sitemaps = Wpfc_New_Dialog.clone.find("div[wpfc-cdn-page='sitemap'] ul.keyword-item-list li.keyword-item a.keyword-label");
						
						if(sitemaps.length > 0){
							Wpfc_New_Dialog.clone.find("div[wpfc-cdn-page='sitemap'] label.wiz-error-msg").text("");
							
							Wpfc_New_Dialog.show_page("details");

							Wpfc_New_Dialog.hide_button("next");

							Wpfc_New_Dialog.show_button("back");
							Wpfc_New_Dialog.show_button("finish");

						}else{
							Wpfc_New_Dialog.clone.find("div[wpfc-cdn-page='sitemap'] label.wiz-error-msg").text("<?php _e('At least one sitemap must be added', 'wp-fastest-cache'); ?>");

						}
					}
				}
			}, function(dialog){
				Wpfc_New_Dialog.show_button("next");
				// Wpfc_New_Dialog.show_button("back");
				// Wpfc_New_Dialog.show_button("finish");

				self.sort();
				self.insert_sitemap_urls(dialog.id);

				jQuery("#" + dialog.id).find("div[wpfc-cdn-page='default'] div.preload_sortable_area").sortable({
				    update: function(event,ui){
				    	self.update_style(dialog.id);
				    }
			    });

			    WPFC_SPINNER.init("wpfc-form-spinner-preload", 6);
			    
			});


		},
		click_event_for_checkbox: function(){
			let self = this;

			jQuery("#wpFastestCachePreload").click(function(){
				if(jQuery(this).is(':checked')){
					if(jQuery("div[id^='wpfc-modal-preload-']").length === 0){
						self.open_modal();
					}
				}
			});
			
		}
	}

	WpFcPreload.init();









</script>