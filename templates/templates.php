<?php
/**
 * Javascript Templates
 *
 * This template can be overridden by copying it to yourtheme/wccontour/templates.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- Empty component -->
<script type="text/html" id="tmpl-wccon-empty-component">
			<div class="wccon-component__body">
				<button class="wccon-choose-button">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<path fill="none" d="M0 0h24v24H0V0z" />
						<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z" />
					</svg>
				</button>
				<button class="wccon-collapse-button">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
					</svg>
				</button>
			</div>
		</script>
		<!-- Empty component -->
		<!-- Filled component -->

		<script type="text/html" id="tmpl-wccon-filled-component">
			<div class="wccon-component__body" >
				<div class="wccon-component-product-item">
					<div class="wccon-component-product" data-product-id="{{{data.product_id}}}">
						<div class="wccon-component-product__image">
							{{{data.image}}}
						</div>
						<div class="wccon-component-product__body">
							<a href="{{{data.href}}}" class="wccon-component-product__title" target="_blank">
								{{{data.title}}}
							</a>
							<# if(data.attributes.length) { #>
								<div class="wccon-component-product__variations">
								<# for(let attribute of data.attributes) {#>
									<# if(attribute.type === 'color') { #>
									<div class="product-attribute-type__color" style="background-color:{{{attribute.value}}}"></div>
									<# } 
									else { #>
										<div class="product-attribute-type__button">{{{attribute.name}}}</div>
									<# } #>
								<# } #>
								</div>
							<# } #>
							<div class="wccon-component-product__meta">
								<# if(data.sku !== '') { #>
									<div class="wccon-component-product__sku">
										{{{data.sku}}}
									</div>
								<# } #>
								
							</div>
							
						</div>

					</div>
					<#
					var productIsSoldIndiv = data.sold_individually ? data.sold_individually : false;
					if(productIsSoldIndiv) { #>
						<div class="wccon-component-product__quantity issoldind"></div>
					<# } 
					else if(data.stock_status === 'instock') { #>
					<div class="wccon-component-product__quantity">
						<button class="minus">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
								<path d="M5 10 H15" fill="none" stroke="currentColor" stroke-width="2" />
							</svg>
						</button>
						<input type="number" class="" step="1" min="1" max="{{{data.stock}}}" name="" value="{{{data.quantity}}}" size="4" inputmode="" />
						<button class="plus">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
								  <path d="M5 10 H15 M10 5 V15" fill="none" stroke="currentColor" stroke-width="2" />
							</svg>
						</button>
					</div>
					<# } 
					else if(data.stock_status === 'outofstock') { #>
						<div class="wccon-component-product__quantity wccon-component-product__outstock"><?php esc_html_e( 'Outofstock', 'wccontour' ); ?></div>
					<# }  #>
				
					<div class="wccon-component-product__price">
						{{{data.price}}}
					</div>
					<div class="wccon-component-buttons">		
						<button class="wccon-refresh-button">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
								<path d="M21 21a1 1 0 0 1-1-1V16H16a1 1 0 0 1 0-2h5a1 1 0 0 1 1 1v5A1 1 0 0 1 21 21zM8 10H3A1 1 0 0 1 2 9V4A1 1 0 0 1 4 4V8H8a1 1 0 0 1 0 2z" />
								<path d="M12 22a10 10 0 0 1-9.94-8.89 1 1 0 0 1 2-.22 8 8 0 0 0 15.5 1.78 1 1 0 1 1 1.88.67A10 10 0 0 1 12 22zM20.94 12a1 1 0 0 1-1-.89A8 8 0 0 0 4.46 9.33a1 1 0 1 1-1.88-.67 10 10 0 0 1 19.37 2.22 1 1 0 0 1-.88 1.1z" />
							</svg>
						</button>

						<button class="wccon-close-button">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" enable-background="new 0 0 24 24" viewBox="0 0 24 24">
								<path d="M13.4,12l6.3-6.3c0.4-0.4,0.4-1,0-1.4c-0.4-0.4-1-0.4-1.4,0L12,10.6L5.7,4.3c-0.4-0.4-1-0.4-1.4,0c-0.4,0.4-0.4,1,0,1.4  l6.3,6.3l-6.3,6.3C4.1,18.5,4,18.7,4,19c0,0.6,0.4,1,1,1c0.3,0,0.5-0.1,0.7-0.3l6.3-6.3l6.3,6.3c0.2,0.2,0.4,0.3,0.7,0.3  s0.5-0.1,0.7-0.3c0.4-0.4,0.4-1,0-1.4L13.4,12z" />
							</svg>
						</button>
						<button class="wccon-collapse-button">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
							</svg>
						</button>
					</div>	
				</div>
			</div>
		</script>
		<!-- Filled component -->
		<!-- Empty subgroup -->
		<script type="text/html" id="tmpl-wccon-empty-subgroup">
		<div class="wccon-component__body">
			<button class="wccon-choose-button wccon-collapse-group">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
				</svg>
			</button>
		</div>
		</script>
		<!-- Empty subgroup -->
		<!-- Filled subgroup -->
		<script type="text/html" id="tmpl-wccon-filled-subgroup">
			<div class="wccon-component__body" >
				<div class="wccon-component-product-item">
					<div class="wccon-subgroup-products">
					<#
					let product_index = 30;
					for(let wcconProduct of data.products) {
						product_index--;
						#>
						<div class="wccon-component-product" style="--proIndex:{{{product_index}}}">
							<div class="wccon-component-product__image">
								{{{wcconProduct.image}}}
							</div>
							<div class="wccon-component-product__body">
								<a href="{{{data.href}}}" class="wccon-component-product__title" target="_blank">
									{{{wcconProduct.title}}}
								</a>
							</div>

						</div>
						<# } #>
					</div>
					<div class="wccon-component-buttons">		
					
						<button class="wccon-choose-button wccon-collapse-group">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
							</svg>
						</button>
					
					</div>	
				</div>
			</div>
		</script>
		<!-- Filled subgroup -->
