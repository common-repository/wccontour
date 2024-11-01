( function ( $ ) {
	'use strict';

	const isMobile = window.matchMedia( '(max-width: 576px)' ).matches;
	const isTablet = window.matchMedia( '(max-width: 768px)' ).matches;

	const symbolCurrency = WCCON_BUILDER_FRONT.currency_format_symbol,
		pricePosition = WCCON_BUILDER_FRONT.price_position,
		wcDecimals = WCCON_BUILDER_FRONT.decimals ? WCCON_BUILDER_FRONT.decimals : 2,
		decimalSep = WCCON_BUILDER_FRONT.decimal_separator ? WCCON_BUILDER_FRONT.decimal_separator : '.',
		thousandSep = WCCON_BUILDER_FRONT.thousand_separator;

	function wccon_price( price ) {
		function addThousandSep( n ) {
			const rx = /(\d+)(\d{3})/;
			return String( n ).replace( /^\d+/, function ( w ) {
				while ( rx.test( w ) ) {
					w = w.replace( rx, '$1' + thousandSep + '$2' );
				}
				return w;
			} );
		}
		let priceString = price.toFixed( wcDecimals );
		priceString = priceString.replace( '.', decimalSep );
		if ( thousandSep ) {
			priceString = addThousandSep( priceString );
		}
		switch ( pricePosition ) {
			case 'left':
				priceString = `${ symbolCurrency }${ priceString }`;
				break;
			case 'right':
				priceString = `${ priceString }${ symbolCurrency }`;
				break;
			case 'left_space':
				priceString = `${ symbolCurrency } ${ priceString }`;
				break;
			case 'right_space':
				priceString = `${ priceString } ${ symbolCurrency }`;
				break;
		}

		return priceString;
	}
	function wccon_parsenumber( number ) {
		if ( parseInt( number, 10 ) < 1 || isNaN( parseInt( number, 10 ) ) ) {
			return 0;
		}
		return parseInt( number, 10 );
	}
	function wccon_clear_products( data ) {
		return data.map( ( group ) => {
			if ( group.hasOwnProperty( 'components' ) ) {
				return {
					...group,
					components: wccon_clear_products( group.components ),
				};
			}
			return {
				...group,
				products: [],
			};
		} );
	}

	function wccon_find_component( slug, data ) {
		let foundComponent = null;

		for ( const el of data ) {
			if ( el.slug === slug ) {
				foundComponent = el;
				break;
			}
			if ( el.hasOwnProperty( 'components' ) ) {
				foundComponent = wccon_find_component( slug, el.components );
			}
		}
		return foundComponent;
	}

	function wccon_change_component( slug, data, value, type, subMultiple = false ) {
		return data
			.map( ( group ) => {
				if ( group.slug === slug ) {
					if ( type === 'add' ) {
						console.log( 'type:add' );
						if ( group.multiple ) {
							const filteredProducts = group.products.filter(
								( p ) => parseInt( p.clone, 10 ) !== parseInt( value.clone, 10 )
							);
							return {
								...group,
								products: [ ...filteredProducts, value ],
							};
						}
						return {
							...group,
							products: [ value ],
						};
					} else {
						return {
							...group,
							products: group.products
								.filter( ( p ) => {
									if ( group.multiple ) {
										return parseInt( p.clone, 10 ) !== parseInt( value.clone, 10 );
									}
									return p.product_id !== value.product_id;
								} )
								.map( ( p, index ) => ( {
									...p,
									clone: index,
								} ) ),
						};
					}
				}
				if ( group.hasOwnProperty( 'components' ) ) {
					let groupMultiple = false;
					if ( group.hasOwnProperty( 'multiple' ) && group.multiple ) {
						groupMultiple = true;
					}
					return {
						...group,
						components: wccon_change_component( slug, group.components, value, type, groupMultiple ),
					};
				}
				return group;
			} )
			.filter( ( c ) => c !== null );
	}

	function wccon_change_product( slug, id, variationId, data, value ) {
		return data.map( ( group ) => {
			if ( group.slug === slug ) {
				return {
					...group,
					products: group.products.map( ( p ) =>
						p.product_id === id || p.variation_id === id ? { ...p, quantity: value } : p
					),
				};
			}
			if ( group.hasOwnProperty( 'components' ) ) {
				return {
					...group,
					components: wccon_change_product( slug, id, variationId, group.components, value ),
				};
			}
			return group;
		} );
	}

	function wccon_resize_window() {
		const winHeight = window.innerHeight;
		$( '.wccon-product-list .aside' ).css( { height: winHeight - 70 + 'px', 'max-height': 'none' } );
		const wcconModelEl = $( '.wccon-modal' );

		if ( wcconModelEl.length ) {
			const isSmall = winHeight < 660 || window.innerWidth < 1240;

			wcconModelEl.each( ( i, el ) => {
				const scrlContainer = $( el ).find( '.wccon-saved-lists' );
				const modalInner = $( el ).find( '.wccon-modal-inner' );
				const modalHeader = $( el ).find( '.wccon-modal__header' );
				const buttonContainer = $( el ).find( '.wccon-button-container' );
				const modalInnerHeight = modalInner.outerHeight();
				const modalHeaderHeight = modalHeader.outerHeight();
				let buttonContainerHeight = buttonContainer.outerHeight();
				if ( ! buttonContainerHeight ) {
					buttonContainerHeight = 50;
				}
				let bottomGap = 150;
				if ( isTablet ) {
					bottomGap = 50;
				}
				const neededHeight = winHeight - modalHeaderHeight - buttonContainerHeight - bottomGap;
				scrlContainer.height( neededHeight );
			} );
		}
	}

	function wccon_resize_window_el( $el ) {
		const winHeight = window.innerHeight;
		const wcconModelEl = $( $el );
		const scrlContainer = wcconModelEl.find( '.wccon-saved-lists' );
		const modalInner = wcconModelEl.find( '.wccon-modal-inner' );
		const modalHeader = wcconModelEl.find( '.wccon-modal__header' );
		const buttonContainer = wcconModelEl.find( '.wccon-button-container' );
		const modalInnerHeight = modalInner.outerHeight();
		const modalHeaderHeight = modalHeader.outerHeight();
		let buttonContainerHeight = buttonContainer.outerHeight();
		if ( ! buttonContainerHeight ) {
			buttonContainerHeight = 50;
		}
		let bottomGap = 150;
		if ( isTablet ) {
			bottomGap = 50;
		}
		const neededHeight = winHeight - modalHeaderHeight - buttonContainerHeight - bottomGap;
		scrlContainer.height( neededHeight );
	}

	function wccon_get_components_products( slug, data, products = [] ) {
		let newProducts = products;

		for ( const group of data ) {
			if ( group.slug === slug ) {
				if ( group.hasOwnProperty( 'components' ) ) {
					for ( const component of group.components ) {
						newProducts = [ ...newProducts, ...component.products ];
					}
				} else {
					newProducts = [ ...products, ...group.products ];
				}
				break;
			}
			if ( group.hasOwnProperty( 'components' ) ) {
				if ( newProducts.length ) {
					break;
				}
				newProducts = wccon_get_components_products( slug, group.components, newProducts );
			}
		}
		console.log( 'Component products:', newProducts );
		return newProducts;
	}

	function wccon_retrieve_products( data, $result = [] ) {
		for ( const group of data ) {
			if ( group.hasOwnProperty( 'components' ) ) {
				for ( const component of group.components ) {
					if ( component.hasOwnProperty( 'products' ) && component.products.length ) {
						$result = [ ...$result, ...component.products ];
					}
					if ( component.hasOwnProperty( 'components' ) ) {
						$result = wccon_retrieve_products( component.components, $result );
					}
				}
			}
			if ( group.hasOwnProperty( 'products' ) && group.products.length ) {
				$result = [ ...$result, ...group.products ];
			}
		}

		return $result;
	}

	function wccon_current_language() {
		if ( WCCON_BUILDER_FRONT.languages && Array.isArray( WCCON_BUILDER_FRONT.languages ) ) {
			const foundLang = WCCON_BUILDER_FRONT.languages.find( ( l ) => l.active == 1 || l.active );
			if ( foundLang ) {
				return foundLang.code;
			}
			return null;
		}
		return null;
	}

	function wccon_generate_skeleton_list( location, countItems, append = true, withoutParent = false ) {
		const notAppanadle = [];
		const neededLocation = $( document.body ).find( `${ location }` );
		if ( ! neededLocation.length ) {
			return;
		}
		const skeletonItemsWrapper = $( '<div />', { class: 'wccon-saved-lists wccon-skeleton-saved-lists' } );
		// const skeletonItemsScrl = $( '<div />', { class: 'wccon-saved-lists' } );
		for ( let i = 0; i < countItems; i++ ) {
			const skeletonContainer = $( '<div/>', { class: 'wccon-skeleton-item' } );
			const skeletonHead = $( '<div/>', { class: 'wccon-skeleton-head' } );
			const skeletonBody = $( '<div/>', { class: 'wccon-skeleton-body' } );
			const skeletonBodyLeft = $( '<div/>', { class: 'wccon-skeleton-body__left' } );
			const skeletonBodyRight = $( '<div/>', { class: 'wccon-skeleton-body__right' } );
			const skeletonSavedItems = $( '<ul/>', { class: 'wccon-skeleton-list' } );
			const skeletonFooter = $( '<div/>', { class: 'wccon-skeleton-footer' } );
			const skeletonFooterLeft = $( '<div/>', {
				class: 'wccon-skeleton-footer__left',
			} );
			const skeletonFooterRight = $( '<div/>', {
				class: 'wccon-skeleton-footer__right',
			} );
			skeletonFooter.append( skeletonFooterLeft );
			skeletonFooter.append( skeletonFooterRight );
			for ( let k = 0; k < 6; k++ ) {
				const skeletonSavedItem = $( '<li/>', { class: 'wccon-skeleton-list__item' } );
				skeletonSavedItems.append( skeletonSavedItem );
			}
			skeletonBodyRight.append( skeletonSavedItems );

			skeletonBody.append( skeletonBodyLeft );
			skeletonBody.append( skeletonBodyRight );

			skeletonContainer.append( skeletonHead );
			skeletonContainer.append( skeletonBody );
			skeletonContainer.append( skeletonFooter );

			notAppanadle.push( skeletonContainer );
		}
		if ( append ) {
			if ( withoutParent ) {
				neededLocation.append( notAppanadle );
			} else {
				skeletonItemsWrapper.append( notAppanadle );
				neededLocation.append( skeletonItemsWrapper );
			}
		}

		if ( ! append ) {
			neededLocation.html(
				notAppanadle
					.map( ( el ) => {
						if ( el.length ) {
							return el.get( 0 ).outerHTML;
						}
						return null;
					} )
					.filter( ( el ) => el !== null )
					.join( '' )
			);
		}
		// wccon_resize_window();
	}

	function wccon_generate_skeleton_builder( location, countItems, replaceContent = false ) {
		const notAppanadle = [];
		const neededLocation = $( location );
		if ( ! neededLocation.length ) {
			return;
		}
		const skeletonItemsWrapper = $( '<div />', { class: 'wccon-product-list' } );
		const skeletonAside = $( '<div />', { class: 'wccon-skeleton-aside' } );
		const filterItemArray = [];
		for ( let i = 0; i < 3; i++ ) {
			const skeletonFilterItem = $( '<div />', { class: 'wccon-skeleton-filter-item' } );
			const skeletonFilterHead = $( '<div/>', { class: 'wccon-skeleton-filter-head' } );
			const skeletonFilterList = $( '<div/>', { class: 'wccon-skeleton-filter-list' } );
			const filterItemsArray = [];
			for ( let k = 0; k < 5; k++ ) {
				const skeletonFilterListItem = $( '<div/>', { class: 'wccon-skeleton-filter-list__item' } );
				const skeletonFilterListCheck = $( '<div/>', { class: 'wccon-skeleton-filter-list__check' } );
				const skeletonFilterListLabel = $( '<div/>', { class: 'wccon-skeleton-filter-list__label' } );
				skeletonFilterListItem.append( skeletonFilterListCheck );
				skeletonFilterListItem.append( skeletonFilterListLabel );
				filterItemsArray.push( skeletonFilterListItem );
			}
			skeletonFilterList.append( filterItemsArray );
			skeletonFilterItem.append( skeletonFilterHead );
			skeletonFilterItem.append( skeletonFilterList );
			filterItemArray.push( skeletonFilterItem );
		}
		skeletonAside.append( filterItemArray );
		const skeletonContent = $( '<div />', { class: 'wccon-skeleton-content' } );
		const skeletonContentTop = $( '<div />', { class: 'wccon-skeleton-content-top' } );
		const skeletonContentTopOne = $( '<div />', { class: 'wccon-skeleton-content-top__one' } );
		const skeletonContentTopTwo = $( '<div />', { class: 'wccon-skeleton-content-top__two' } );
		const skeletonContentTopThree = $( '<div />', { class: 'wccon-skeleton-content-top__three' } );
		skeletonContentTop.append( skeletonContentTopOne );
		skeletonContentTop.append( skeletonContentTopTwo );
		skeletonContentTop.append( skeletonContentTopThree );

		const skeletonProductContainer = $( '<div />', { class: 'wccon-skeleton-product-items wccon-scrl-container' } );

		for ( let i = 0; i < countItems; i++ ) {
			const skeletonProductItem = $( '<div/>', { class: 'wccon-skeleton-product-item' } );
			const skeletonProdutImage = $( '<div/>', { class: 'wccon-skeleton-product-image' } );
			const skeletonProductBody = $( '<div/>', { class: 'wccon-skeleton-product-body' } );
			const skeletonProductBodyOne = $( '<div/>', { class: 'wccon-skeleton-product-body__one' } );
			const skeletonProductBodyTwo = $( '<div/>', { class: 'wccon-skeleton-product-body__two' } );
			const skeletonProductBodyThree = $( '<div/>', { class: 'wccon-skeleton-product-body__three' } );
			const skeletonProductActions = $( '<div/>', { class: 'wccon-skeleton-product-actions' } );

			skeletonProductBody.append( skeletonProductBodyOne );
			skeletonProductBody.append( skeletonProductBodyTwo );
			skeletonProductBody.append( skeletonProductBodyThree );

			skeletonProductItem.append( skeletonProdutImage );
			skeletonProductItem.append( skeletonProductBody );
			skeletonProductItem.append( skeletonProductActions );

			notAppanadle.push( skeletonProductItem );
		}

		skeletonProductContainer.append( notAppanadle );

		skeletonContent.append( skeletonContentTop );
		skeletonContent.append( skeletonProductContainer );

		skeletonItemsWrapper.append( skeletonAside );
		skeletonItemsWrapper.append( skeletonContent );
		if ( replaceContent ) {
			neededLocation.html( skeletonItemsWrapper );
		} else {
			skeletonItemsWrapper.insertAfter( neededLocation );
		}
	}

	function wccon_generate_skeleton_products( location, countItems ) {
		const notAppanadle = [];
		const skeletonProductContainer = $( '<div />', { class: 'wccon-skeleton-product-items' } );

		for ( let i = 0; i < countItems; i++ ) {
			const skeletonProductItem = $( '<div/>', { class: 'wccon-skeleton-product-item' } );
			const skeletonProdutImage = $( '<div/>', { class: 'wccon-skeleton-product-image' } );
			const skeletonProductBody = $( '<div/>', { class: 'wccon-skeleton-product-body' } );
			const skeletonProductBodyOne = $( '<div/>', { class: 'wccon-skeleton-product-body__one' } );
			const skeletonProductBodyTwo = $( '<div/>', { class: 'wccon-skeleton-product-body__two' } );
			const skeletonProductBodyThree = $( '<div/>', { class: 'wccon-skeleton-product-body__three' } );
			const skeletonProductActions = $( '<div/>', { class: 'wccon-skeleton-product-actions' } );

			skeletonProductBody.append( skeletonProductBodyOne );
			skeletonProductBody.append( skeletonProductBodyTwo );
			skeletonProductBody.append( skeletonProductBodyThree );

			skeletonProductItem.append( skeletonProdutImage );
			skeletonProductItem.append( skeletonProductBody );
			skeletonProductItem.append( skeletonProductActions );

			notAppanadle.push( skeletonProductItem );
		}

		skeletonProductContainer.append( notAppanadle );
		location.find( '.wccon-product-items' ).replaceWith( skeletonProductContainer );
	}

	function wccon_sort_by_position( a, b ) {
		const positionOne = wccon_parsenumber( a.position );
		const positionTwo = wccon_parsenumber( b.position );
		if ( positionOne > positionTwo ) {
			return 1;
		}
		if ( positionOne < positionTwo ) {
			return -1;
		}
		return 0;
	}

	function wcconTrapFocus( element, type = 'on' ) {
		const focusableEls = element.find(
			'a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])'
		);

		const firstFocusableEl = focusableEls.first();
		const lastFocusableEl = focusableEls.last();

		const KEYCODE_TAB = 9;
		const callBackFunc = function ( e ) {
			const isTabPressed = e.key === 'Tab' || e.keyCode === KEYCODE_TAB;

			if ( ! isTabPressed ) {
				return;
			}
			$( 'html, body' ).animate( { scrollTop: element.offset().top }, 0 );
			const target = $( e.target );

			if ( e.shiftKey ) {
				/* shift + tab */ if ( target.is( element ) || target.is( firstFocusableEl ) ) {
					e.preventDefault();
					lastFocusableEl.focus();
				}
			} /* tab */ else {
				if ( target.is( lastFocusableEl ) ) {
					e.preventDefault();
					firstFocusableEl.focus();
				}
			}
		};
		if ( type === 'on' ) {
			element.attr( 'tabindex', '-1' ).focus();

			element.on( 'keydown.wccon', callBackFunc );
		} else {
			element.off( 'keydown.wccon' );
		}
	}

	class WCCFilterWidget {
		constructor( el ) {
			this.searchTimeOut;
			this.productRequest = null;
			this.compatibilityRequest = null;
			this.builderWrapper = $( el );
			this.builderId = this.builderWrapper.attr( 'data-wccon-builder' );
			this.builderWrapperData = JSON.parse( this.builderWrapper.attr( 'data-wccon-scheme' ) );
			this.ajaxUrl = WCCON_BUILDER_FRONT.ajax_url;
			this.compatiblityEnabled = WCCON_BUILDER_FRONT.compatibility_enabled;
			this.successMessage = WCCON_BUILDER_FRONT.i18n_success_message;
			this.copiedMessage = WCCON_BUILDER_FRONT.i18n_copied_message;
			this.errorMessage = WCCON_BUILDER_FRONT.i18n_error_message;
			this.addedToCartMessage = WCCON_BUILDER_FRONT.i18n_added_to_cart;
			this.saveToStorage = WCCON_BUILDER_FRONT.local_storage;
			this.soldIndividually = WCCON_BUILDER_FRONT.sold_individually;
			this.nonce = WCCON_BUILDER_FRONT.nonce;

			this.currentLang = wccon_current_language();

			const searchParams = new URLSearchParams( window.location.search );
			this.savedListQuery = searchParams.get( 'wccon-list' );

			this.savedList = localStorage.getItem( 'wccon-list' );

			//maybe clear removed builder list before load.
			this.maybeClearLists.bind( this )();

			console.log(
				'Builder data vs storage Data before:',
				this.builderWrapperData,
				JSON.parse( this.savedList )
			);
			this.productScheme = this.rebuildList.bind( this )();
			console.log( 'Builder data vs storage Data after:', this.builderWrapperData, JSON.parse( this.savedList ) );

			console.log( 'Product scheme', this.productScheme );
			this.componentContainer = $( el ).find( '.wccon-component' ).first();
			this.componentHead = this.builderWrapper.find( '.wccon-component-inner' );
			this.addCartButton = this.builderWrapper.find( '.wccon-add-to-cart' );
			this.multipleButtons = this.builderWrapper.find( '.wccon-component-product__multiple' );
			this.openedContent = true;

			this.totalsContainer = this.builderWrapper.find( '.wccon-bottom-block' );

			//top bar elements
			this.saveListButton = this.builderWrapper.find( '.wccon-save-list-button' );
			this.clearListButton = this.builderWrapper.find( '.wccon-clear-list-button' );
			this.shareListButton = this.builderWrapper.find( '.wccon-share-list-button' );
			this.shareListButtonClose = this.builderWrapper.find( '.wccon-share-close' );

			this.openMyLists = this.builderWrapper.find( '.wccon-open-user-lists' );
			this.openUsersLists = this.builderWrapper.find( '.wccon-open-lists' );
			this.closeModalButton = $( document.body ).find( '.wccon-modal__close' );
			//datastore

			this.dataStore = this.productScheme.groups;
			this.incompatibleData = [];
			this.totalPrice = 0;
			this.totalPriceField = this.builderWrapper.find( '.wccon-total-price' );
			this.timeOutAnimationLoading = wp.hooks.applyFilters( 'wccon-timeout-loading', 800 );
			this.loadListTimeOut = null;

			this.isMobile = window.matchMedia( '(max-width: 576px)' ).matches;
			this.isTablet = window.matchMedia( '(max-width: 768px)' ).matches;
			this.isDesktop = window.matchMedia( '(min-width: 998px)' ).matches;

			//tooltips
			this.toolTips = this.builderWrapper.find( '.wccon-tooltip' );
			this.tippyInstances = [];
			this.initTooltips();

			//extra components
			this.toggleExtraContainer = this.builderWrapper.find( '.wccon-component-extra' );

			if ( this.isListExists() ) {
				this.runCompatibilitySaved();
			}
			//this.componentHead.on( 'click', this.maybeGetProducts.bind( this ) );
			this.builderWrapper
				.on( 'wccon-products-loaded', ( e, componentSlug ) => {
					this.initProperties( 'init' );

					// this.componentHead.off().on( 'click', this.maybeGetProducts.bind( this ) );
					this.initEvents();
					this.initPriceWidget( 'init' );
				} )
				.on( 'wccon-filter-end', ( e, componentSlug ) => {
					this.initProperties( 'ajax' );
					this.initEvents();

					this.initPriceWidget( 'ajax' );
				} );
			const _this = this;

			//
			this.builderWrapper
				.on( 'click', '.wccon-refresh-button', this.editProduct.bind( this ) )
				.on( 'click', '.wccon-close-button', this.removeProduct.bind( this ) )
				.on( 'click', '.wccon-collapse-button', this.collapseProducts.bind( this ) )
				.on( 'click', '.wccon-component-product__multiple', this.addMultiple.bind( this ) )
				//sorting
				.on( 'click', '.wccon-dropdown-select', this.handleSelect.bind( this ) )
				.on( 'click', '.wccon-dropdown-select li a', this.handleSorting.bind( this ) )
				.on( 'click', '.product-attribute-group__nice-select', this.handleVariationSelectOpen.bind( this ) )
				.on( 'click', '.product-attribute-group__nice-select li', this.handleVariationSelect.bind( this ) )
				.on(
					'click',
					'.product-attribute-group__color li, .product-attribute-group__button li',
					this.handleVariationColor.bind( this )
				)
				.on( 'click', '.wccon-pagination-item', this.handlePagination.bind( this ) )

				.on( 'click', '.wccon-product-title .desc-toggler', this.handleExpandDescription.bind( this ) )
				//templating
				.on( 'click', '.product-item__add', this.chooseProduct.bind( this ) )

				//custom events.
				.on( 'wccon-load-list-start', this.loadListStart.bind( this ) )
				.on( 'wccon-load-list-end', this.loadListEnd.bind( this ) )
				.on( 'click', '.wccon-reset_variations', this.resetVariations.bind( this ) )
				.on( 'click', '.wccon-widget-head', this.toggleWidget )
				.on( 'click', '.widget-expander-show', this.expandWidget )
				.on( 'click', '.widget-expander-hide', this.collapseWidget )
				.on( 'click', '.wccon-clear-all', this.clearFilters.bind( this ) )
				.on( 'click', '.wccon-filters-btn', this.openFilters )
				.on( 'click', '.wccon-close-filters', this.closeFilters )
				.on( 'click', '.wccon-component-inner', this.maybeGetProducts.bind( this ) )
				.on( 'click', '.share-link', this.copyLink.bind( this ) )
				.on( 'click', '.plus, .minus', function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					let quantityVal = 1;
					const $qty = $( this ).siblings( 'input' ),
						currentVal = parseFloat( $qty.val() ),
						max = parseFloat( $qty.attr( 'max' ) ),
						min = parseFloat( $qty.attr( 'min' ) ),
						step = $qty.attr( 'step' );

					// Format values
					if ( ! currentVal || currentVal === '' || currentVal === 'NaN' ) currentVal = 0;
					if ( max === '' || max === 'NaN' ) max = '';
					if ( min === '' || min === 'NaN' ) min = 0;
					if ( step === 'any' || step === '' || step === undefined || parseFloat( step ) === 'NaN' ) step = 1;

					// Change the value
					if ( $( this ).is( '.plus' ) ) {
						if ( max && currentVal >= max ) {
							$qty.val( max );
							quantityVal = max;
						} else {
							$qty.val( currentVal + parseFloat( step ) );
							quantityVal = currentVal + parseFloat( step );
						}
					} else {
						if ( min && currentVal <= min ) {
							$qty.val( min );
							quantityVal = min;
						} else if ( currentVal > 0 ) {
							$qty.val( currentVal - parseFloat( step ) );
							quantityVal = currentVal - parseFloat( step );
						}
					}
					$qty.trigger( 'change' );

					const productId = $( this )
						.closest( '.wccon-component-product-item' )
						.find( '.wccon-component-product' )
						.attr( 'data-product-id' );

					const variationId = $( this )
						.closest( '.wccon-component-product-item' )
						.find( '.wccon-component-product' )
						.attr( 'data-variation-id' );

					const componentSlug = $( this ).closest( '.wccon-component' ).attr( 'data-component-slug' );
					_this.dataStore = wccon_change_product(
						componentSlug,
						productId,
						variationId,
						_this.productScheme.groups,
						quantityVal
					);
					_this.productScheme = {
						..._this.productScheme,
						groups: _this.dataStore,
					};
					_this.maybeSaveToStorage();

					_this.totalPrice = _this.calculateTotals();
					_this.totalPriceField.html( wccon_price( _this.totalPrice ) );
				} );
			this.addCartButton.on( 'click', this.buyProduct.bind( this ) );

			// this.initPriceWidget( 'init' );
			//top bar events
			this.saveListButton.on( 'click', this.saveList.bind( this ) );
			this.clearListButton.on( 'click', this.clearList.bind( this ) );
			this.shareListButton.on( 'click', this.shareList );
			this.shareListButtonClose.on( 'click', this.shareList );
			this.openUsersLists.on( 'click', this.openUsersListsHandler );
			this.openMyLists.on( 'click', this.openMyListsHandler );
			this.closeModalButton.on( 'click', this.closeModal );

			//other events
			this.toggleExtraContainer.on( 'click', this.toggleExtraHandler.bind( this ) );

			//maybe load list from localStorage.
			if ( ! this.isListExists() ) {
				this.maybeLoadList.bind( this )();
			}

			//builder loaded event
			$( document.body ).on( 'wccon_builder_loaded', function ( e, builder ) {
				if ( builder.isTablet ) {
					$( '#wccon-share-modal .wccon-modal__body' ).append( $( '.wccon-share-box' ).addClass( 'active' ) );
				}

				//init add to cart button(disabled or enabled)
				const selectedProducts = wccon_retrieve_products( builder.dataStore );
				if ( ! selectedProducts.length ) {
					builder.addCartButton.attr( 'disabled', true );
				}

				//on load account builder. Check width.
				if ( builder.isAccountPage() && builder.builderWrapper.width() < 900 && builder.isDesktop ) {
					builder.builderWrapper.addClass( 'minimal' );
				}
			} );

			//product chosen event
			this.builderWrapper.on( 'wccon-product-chosen', ( e, slug, id, dataStore ) => {
				const selectedProducts = wccon_retrieve_products( dataStore );
				console.log( 'choosen', dataStore, selectedProducts );
				if ( ! selectedProducts.length ) {
					this.addCartButton.attr( 'disabled', true );
				} else {
					this.addCartButton.attr( 'disabled', false );
				}
			} );

			//product removed event
			this.builderWrapper.on( 'wccon-product-removed', ( e, slug, id, dataStore ) => {
				const selectedProducts = wccon_retrieve_products( dataStore );
				console.log( 'removed', selectedProducts );
				if ( ! selectedProducts.length ) {
					this.addCartButton.attr( 'disabled', true );
				} else {
					this.addCartButton.attr( 'disabled', false );
				}
			} );

			//builder cleared
			this.builderWrapper.on( 'wccon-builder-cleared', ( e, builderWrapper ) => {
				this.addCartButton.attr( 'disabled', true );
			} );

			$( document.body ).trigger( 'wccon_builder_loaded', this );
		}
		initProperties( type ) {
			this.componentHead = this.builderWrapper.find( '.wccon-component-inner' );
			this.multipleButtons = this.builderWrapper.find( '.wccon-component-product__multiple' );
			this.form = this.builderWrapper.find( '#wccon-filter-form' );
			this.searchField = this.builderWrapper.find( '#wccon-product-search' );

			this.paginationLinks = this.builderWrapper.find( '.wccon-pagination-item' );
			//query
			this.query = new URLSearchParams();
			//states
			if ( type === 'init' ) {
				this.activeFilters = {
					tax_query: [],
					meta_query: [],
					search: '',
					prices: [],
					pagination: 1,
					stock: 'all',
					orderby: 'menu_order',
				};
				this.maxPrice = 0;
				this.minPrice = 0;
				this.manualChangedPrice = false;
			}
		}
		initEvents() {
			this.form.on( 'change', this.handleForm.bind( this ) );
			this.searchField.on( 'keyup', this.search.bind( this ) );
			//pagination

			const _this = this;
			$( 'select[name^=attribute_]', this.builderWrapper ).on( 'change', function ( e ) {
				const productElement = $( this ).closest( '.wccon-product-item' );
				_this.chooseVariation( productElement );
			} );

			const currentComponent = this.builderWrapper.find(
				`.wccon-component.opened[data-component-slug=${ this.componentSlug }]`
			);
			currentComponent.find( '.wccon-product-item' ).each( ( i, el ) => {
				if ( $( el ).attr( 'data-product_variations' ) ) {
					this.chooseVariation( $( el ) );
				}
			} );
			//add description overflow.
			currentComponent.find( '.product-item__desc' ).each( ( i, el ) => {
				if ( $( el ).get( 0 ).scrollHeight > 43 ) {
					$( el )
						.siblings( '.wccon-product-title' )
						.append( '<span class="desc-toggler"><svg><use xlink:href="#icon-ch-dw"></use></svg></span>' );
					$( el ).addClass( 'desc-over' );
				}
			} );
		}
		initPriceWidget( type ) {
			this.priceFilter = this.builderWrapper.find( '#wccon-slider' ).get( 0 );
			this.priceWidget = this.builderWrapper.find( '.wccon-slider-wrapper' );
			if ( this.priceFilter ) {
				const minPrice = parseFloat( this.priceFilter.dataset.minPrice );
				const maxPrice = parseFloat( this.priceFilter.dataset.maxPrice );
				const startPrice = parseFloat( this.priceFilter.dataset.startPrice );
				const endPrice = parseFloat( this.priceFilter.dataset.endPrice );
				if ( type === 'init' ) {
					this.maxPrice = maxPrice;
					this.minPrice = minPrice;
					this.manualChangedPrice = false;
				}

				const inputPrices = this.priceWidget.find( '.inps' );

				let calcEndPrice = maxPrice;
				if ( this.manualChangedPrice ) {
					calcEndPrice = endPrice > maxPrice ? maxPrice : endPrice;
				} else {
					calcEndPrice = maxPrice > endPrice ? endPrice : maxPrice;
				}
				noUiSlider.create( this.priceFilter, {
					start: [ startPrice, calcEndPrice ],
					connect: true,
					step: 1,
					format: {
						to: ( v ) => parseFloat( v ).toFixed( 0 ),
						from: ( v ) => parseFloat( v ).toFixed( 0 ),
					},
					range: {
						min: 0,
						max: this.maxPrice,
					},
					tooltips: inputPrices.length ? false : true,
				} );

				if ( inputPrices.length > 0 ) {
					const minPriceInput = inputPrices.find( '.inp-min' );
					const maxPriceInput = inputPrices.find( '.inp-max' );
					const priceButton = this.priceWidget.find( '.wccon-price-filter-button' );

					priceButton.on( 'click', ( e ) => {
						e.preventDefault();
						this.manualChangedPrice = true;
						this.buildQueryString();
						this.sendRequest();
					} );
					minPriceInput.on( 'change', ( e ) => {
						this.priceFilter.noUiSlider.set( [ parseInt( e.target.value, 10 ), null ] );
					} );
					maxPriceInput.on( 'change', ( e ) => {
						this.priceFilter.noUiSlider.set( [ null, parseInt( e.target.value, 10 ) ] );
					} );
					this.priceFilter.noUiSlider.on( 'update', ( values, handle ) => {
						const value = values[ handle ];

						this.activeFilters.prices = values;
						if ( handle ) {
							maxPriceInput.val( value );
						} else {
							minPriceInput.val( value );
						}
					} );
				}
				this.priceFilter.noUiSlider.on( 'end', ( values ) => {
					this.activeFilters.prices = values;
					this.manualChangedPrice = true;
					this.buildQueryString();
					this.sendRequest();
				} );
			}
		}
		initTooltips() {
			if ( this.isDesktop ) {
				this.toolTips.each( ( i, el ) => {
					tippy( $( el ).get( 0 ), {
						content: $( el ).data( 'tippy' ),
						popperOptions: {
							modifiers: [ { name: 'eventListeners', enabled: false } ],
						},
					} );
				} );
			}
		}
		rebuildList() {
			if ( this.isListExists() ) {
				console.log( 'rebuildList-1' );
				return this.builderWrapperData;
			}
			if ( ! this.savedList || ( this.savedList && ! this.saveToStorage ) ) {
				console.log( 'rebuildList-2', this.saveToStorage );
				return this.builderWrapperData;
			}

			if(!WCCON_BUILDER_FRONT.nonce2) {
				return this.builderWrapperData;
			}
			
			const parsedSavedList = JSON.parse( this.savedList );
			let newSavedList = null;

			if ( ! newSavedList ) {
				console.log( 'rebuild not found' );
				return this.builderWrapperData;
			}
			console.log( 'New', newSavedList );
			return newSavedList;
		}
		loadListStart( e, listId, savedList ) {
			this.loadListTimeOut = setTimeout( () => {
				$( '.wccon-component-wrapper', this.builderWrapper ).addClass( 'wccon-loading' );
			}, this.timeOutAnimationLoading );

			$.blockUI.defaults.overlayCSS.cursor = 'default';
			this.builderWrapper.block( {
				message: null,
				overlayCSS: {
					background: 'transparent',
				},
				// cursorReset: 'default',
				blockMsgClass: 'wccon-block-message',
			} );
			$( '.blockUI.blockOverlay' ).addClass( 'wccon-block-overlay' );
		}
		loadListEnd( e, listId, savedList, response ) {
			this.builderWrapper.unblock();

			clearTimeout( this.loadListTimeOut );
			this.loadListTimeOut = null;

			$( '.wccon-component-wrapper', this.builderWrapper ).removeClass( 'wccon-loading' );
			$( '.blockUI.blockOverlay' ).removeClass( 'wccon-block-overlay' );
		}

		maybeClearLists() {
			if ( this.savedList && this.saveToStorage && WCCON_BUILDER_FRONT.nonce2 ) {
				const parsedList = JSON.parse( this.savedList );

				if ( ! Array.isArray( parsedList ) ) {
					console.log( 'removed wccon-list' );
					localStorage.removeItem( 'wccon-list' );
					this.savedList = null;
					return;
				}
				let savedShortcodes = Array.isArray( WCCON_BUILDER_FRONT.saved_config_ids )
					? WCCON_BUILDER_FRONT.saved_config_ids
					: [];
				const filteredSaved = parsedList.filter(
					( builder ) => savedShortcodes.indexOf( parseInt( builder.id, 10 ) ) !== -1
				);
				localStorage.setItem( 'wccon-list', JSON.stringify( filteredSaved ) );
			}
		}

		maybeLoadList() {}
		search( e ) {
			//return on shift and Tab key
			if ( e.keyCode === 16 || e.keyCode === 9 ) {
				return;
			}
			if ( this.searchTimeOut ) {
				clearTimeout( this.searchTimeOut );
			}
			this.searchTimeOut = setTimeout( () => {
				this.activeFilters.search = e.target.value;
				this.buildQueryString();
				this.sendRequest( e );
			}, 500 );
		}
		handlePagination( e ) {
			e.preventDefault();
			let paginationLink = e.target.attributes[ 'data-link' ].nodeValue;
			this.activeFilters.pagination = paginationLink;

			this.buildQueryString( true );
			this.sendRequest();
		}
		handleSelect( e ) {
			const _thisELement = $( e.currentTarget );
			const menu = _thisELement.find( 'ul' );
			const icon = _thisELement.find( 'svg' );
			menu.toggleClass( 'active' );
			icon.toggleClass( 'active' );
		}
		handleSorting( e ) {
			e.preventDefault();
			const _thisELement = $( e.currentTarget );
			const selectType = _thisELement.closest( '.wccon-dropdown-select' ).attr( 'data-type' );
			this.activeFilters[ selectType ] = e.target.hash.slice( 1 );

			_thisELement
				.closest( '.wccon-dropdown-select' )
				.find( '.wccon-dropdown-inner span' )
				.text( _thisELement.text() );
			this.buildQueryString();
			this.sendRequest();
		}

		handleVariationSelectOpen( e ) {
			const _thisELement = $( e.currentTarget );
			const menu = _thisELement.find( 'ul' );
			const icon = _thisELement.find( 'svg' );
			menu.toggleClass( 'active' );
			icon.toggleClass( 'active' );
		}
		handleVariationSelect( e ) {
			const _thisELement = $( e.currentTarget );
			const selectValue = _thisELement.closest( 'li' ).attr( 'data-value' );
			if ( _thisELement.closest( 'li' ).hasClass( 'disabled' ) ) {
				return;
			}
			const selectEl = _thisELement.closest( '.product-attribute-group' ).find( 'select' );
			if ( selectEl.length ) {
				_thisELement
					.closest( '.product-attribute-group' )
					.find( '.product-attribute-selected span' )
					.text( _thisELement.text() );
				_thisELement.closest( '.product-attribute-group' ).find( 'li' ).attr( 'aria-selected', 'false' );
				_thisELement.closest( 'li' ).attr( 'aria-selected', 'true' );
				selectEl.val( selectValue );
				selectEl.trigger( 'change' );
			}
		}
		handleVariationColor( e ) {
			const _thisELement = $( e.currentTarget );
			const selectValue = _thisELement.closest( 'li' ).attr( 'data-value' );
			if ( _thisELement.closest( 'li' ).hasClass( 'disabled' ) ) {
				return;
			}
			const selectEl = _thisELement.closest( '.product-attribute-group' ).find( 'select' );
			if ( selectEl.length ) {
				_thisELement.closest( '.product-attribute-group' ).find( 'li' ).attr( 'aria-checked', 'false' );

				_thisELement.closest( 'li' ).attr( 'aria-checked', 'true' );

				selectEl.val( selectValue );
				selectEl.trigger( 'change' );
			}
		}
		manageDropdown( e ) {
			e.preventDefault();
			let option = e.target.attributes[ 'data-option' ].nodeValue;
			$( 'option:selected', this.periodRange ).removeAttr( 'selected' );

			this.periodRange
				.find( 'option' )
				.eq( option - 1 )
				.attr( 'selected', 'selected' );
			$( '.dropdown .dropdown-toggle' ).html(
				this.periodRange
					.find( 'option' )
					.eq( option - 1 )
					.html()
			);
			this.periodRange.trigger( 'change' );
		}

		handleForm( e ) {
			if ( [ 'min_price', 'max_price' ].indexOf( e.target.name ) !== -1 ) {
				return;
			}

			this.buildQueryString();
			this.sendRequest();
		}

		buildQueryString( pagination = false, data = {} ) {
			let $query = this.form.serializeArray();
			console.log( 'Query string:', $query );
			let tax_query = [];
			let meta_query = [];

			$query.forEach( ( el ) => {
				// if ( el.name === 'wccon_tax_filter' ) {
				// 	tax_query.push( el.value );
				// }
				if ( /^wccon_tax_filter/.test( el.name ) ) {
					let tax_name = el.name.replace( 'wccon_tax_filter[', '' ).slice( 0, -1 );
					console.log( 'tax_name', tax_name );

					const taxExists = tax_query.find( ( tq ) => tq.tax_name === tax_name );
					if ( taxExists ) {
						tax_query = tax_query.map( ( tq ) => {
							// const newMetaValue = mq.meta_value.split(',');
							return tq.tax_name === tax_name
								? {
										...tq,
										tax_value: [ ...tq.tax_value, el.value ], // {tax_name: '', tax_value: '11,22,33'}
								  }
								: tq;
						} );
					} else {
						tax_query.push( { tax_name, tax_value: [ el.value ] } );
					}
				}
				if ( /^wccon_meta_filter/.test( el.name ) ) {
					let meta_key = el.name.replace( 'wccon_meta_filter[', '' ).slice( 0, -1 );
					console.log( 'meta_key', meta_key );

					const metaKeyExists = meta_query.find( ( mq ) => mq.meta_key === meta_key );
					if ( metaKeyExists ) {
						meta_query = meta_query.map( ( mq ) => {
							// const newMetaValue = mq.meta_value.split(',');
							return mq.meta_key === meta_key
								? {
										...mq,
										meta_value: [ ...mq.meta_value, el.value ], // {meta_key: '', meta_value: '11,22,33'}
								  }
								: mq;
						} );
					} else {
						meta_query.push( { meta_key, meta_value: [ el.value ] } );
					}
				}
			} );
			this.activeFilters = {
				tax_query,
				meta_query,
				prices: this.activeFilters.prices,
				pagination: pagination ? this.activeFilters.pagination : 1,
				orderby: this.activeFilters.orderby,
				stock: this.activeFilters.stock,
				search: this.searchField.val(),
			};
			console.log( this.activeFilters );
			for ( let key in this.activeFilters ) {
				this.query.delete( key );
				if ( key === 'tax_query' ) {
					this.query.append( key, JSON.stringify( this.activeFilters[ key ] ) );
				} else if ( key === 'meta_query' ) {
					this.query.append( key, JSON.stringify( this.activeFilters[ key ] ) );
				} else if ( key === 'prices' ) {
					if ( this.activeFilters.prices.length === 2 ) {
						//send price only if it was changed

						if (
							( this.minPrice !== parseInt( this.activeFilters.prices[ 0 ], 10 ) ||
								this.maxPrice !== parseInt( this.activeFilters.prices[ 1 ], 10 ) ) &&
							this.manualChangedPrice
						) {
							this.query.append( key, this.activeFilters[ key ].join( ',' ) );
						}
					}
				} else if ( [ 'pagination', 'orderby', 'stock', 'search' ].indexOf( key ) !== -1 ) {
					this.query.append( key, this.activeFilters[ key ] );
				}
			}

			console.log( 'Query string-2', this.query.toString() );
		}
		sendRequest() {
			this.componentContainer = this.builderWrapper.find(
				`.wccon-component[data-component-slug=${ this.componentSlug }]`
			);
			if ( ! this.componentContainer.length ) {
				return;
			}
			const currentItem = this.componentContainer.find( '.wccon-products-body' );
			wccon_generate_skeleton_products( currentItem, 5, true );
			//add selected products
			const selectedProducts = wccon_get_components_products( this.componentSlug, this.productScheme.groups );
			this.query.append( 'selected_products', JSON.stringify( selectedProducts ) );
			$.ajax( {
				url: this.ajaxUrl,
				type: 'POST',
				data:
					`action=wccon_filter_builder&nonce=${ this.nonce }&component_id=${ this.componentId }&` +
					this.query.toString(),
			} )
				.then( ( res ) => {
				
					//remove skeleton
				
					$( currentItem ).find( '.wccon-skeleton-product-items' ).remove();

					console.log( res );
					if ( ! res.success ) {
						return;
					}

					this.componentContainer
						.find( '.wccon-products-body' )
						.replaceWith( $( res.data.html ).find( '.wccon-products-body' ) );
					this.componentContainer.find( 'aside' ).replaceWith( $( res.data.html ).find( 'aside' ) );
					// this.componentContainer
					// 	.find( '.wccon-pagination' )
					// 	.replaceWith( $( res.data.html ).find( '.wccon-pagination' ) );

					this.form = $( document.body ).find( '#wccon-filter-form' );
					// this.builderWrapper = this.form.closest( '.wccon-builder-wrapper' );

					this.builderWrapper.trigger( 'wccon-filter-end', [ this.componentSlug ] );
				} )
				.catch( ( err ) => {
					console.log( err );
				} );
		}
		clearFilters( e ) {
			e.preventDefault();
			this.activeFilters = {
				tax_query: [],
				meta_query: [],
				search: '',
				prices: [],
				pagination: 1,
				stock: 'all',
				orderby: 'menu_order',
			};
			for ( const key in this.activeFilters ) {
				this.query.delete( key );

				if ( key === 'tax_query' ) {
					this.query.append( key, JSON.stringify( this.activeFilters[ key ] ) );
				} else if ( key === 'meta_query' ) {
					this.query.append( key, JSON.stringify( this.activeFilters[ key ] ) );
				} else if ( [ 'pagination', 'orderby', 'stock', 'search' ].indexOf( key ) !== -1 ) {
					this.query.append( key, this.activeFilters[ key ] );
				}
			}
			//restore dropdowns as they are not ajax replaced
			const stockSelect = $( '.wccon-dropdown-select[data-type=stock]' );
			const sortSelect = $( '.wccon-dropdown-select[data-type=orderby]' );
			if ( stockSelect.length ) {
				const stockSpan = stockSelect.find( '.wccon-dropdown-inner span' );
				stockSelect.find( 'li a' ).each( ( i, el ) => {
					if ( $( el ).attr( 'href' ) === '#all' ) {
						stockSpan.text( $( el ).text() );
					}
				} );
			}
			if ( sortSelect.length ) {
				const sortSpan = sortSelect.find( '.wccon-dropdown-inner span' );
				sortSelect.find( 'li a' ).each( ( i, el ) => {
					if ( $( el ).attr( 'href' ) === '#menu_order' ) {
						sortSpan.text( $( el ).text() );
					}
				} );
			}
			this.searchField.val( '' );
			this.sendRequest();
		}
		chooseProduct( e ) {
			e.preventDefault();
			const _thisButton = $( e.currentTarget );

			const componentContainer = _thisButton.closest( '.wccon-component' );
			const isExtraComponent = componentContainer.hasClass( 'extra' );
			componentContainer.addClass( 'selected' );
			if ( isExtraComponent ) {
				componentContainer.removeClass( 'exhide' );
			}
			const productSlug = componentContainer.attr( 'data-component-slug' );
			const componentMultiple = componentContainer.attr( 'data-multiple' );

			const productBodyContainer = componentContainer.find( '.wccon-component-inner .wccon-component__body' );
			const componentIcon = componentContainer.find( '.wccon-component-inner .wccon-component__icon' );
			const compatibilityIcon = componentContainer.find( '.wccon-component__selected' );
			componentIcon.hide();

			const productItem = _thisButton.closest( '.wccon-product-item' );

			const productId =
				typeof productItem.attr( 'data-variation-id' ) !== 'undefined' &&
				this.maybeStringToNumber( productItem.attr( 'data-variation-id' ), false ) > 0
					? productItem.attr( 'data-variation-id' )
					: productItem.attr( 'data-product-id' );
			const variationId = productItem.attr( 'data-variation-id' );
			const productPrice = _thisButton.siblings( '.product-item__price' ).find( '.price' ).html();

			const productTitle = productItem.find( '.wccon-product-title' ).text();
			const productLink = _thisButton
				.closest( '.wccon-product-item' )
				.find( '.wccon-product-title' )
				.attr( 'href' );
			const productSku = productItem.find( '.wccon-product-sku span' ).text();
			const productImage = productItem.find( '.product-item__image' ).html();
			const productStockStatus = productItem.find( '.stock' );
			const productPriceRaw = productItem.attr( 'data-price' );
			const productData = productItem.data( 'product-info' );

			const templateSelected = wp.template( 'wccon-filled-component' );

			//is variation.
			const productAttributesData = [];
			const productAttributes = productItem.find( '.product-item__attributes' );
			$( '.product-attribute-group', productAttributes ).each( ( i, el ) => {
				let value = $( el ).find( 'select' ).val();
				let attrName = $( el ).find( 'select option:selected' ).text();
				const attrType = $( el ).hasClass( 'product-attribute-group__color' ) ? 'color' : 'button';
				if ( attrType === 'color' ) {
					const checkedLi = $( el ).find( 'li[aria-checked="true"]' );
					value = checkedLi.find( 'div' ).css( 'background-color' );
				}
				productAttributesData.push( { type: attrType, value: value, name: attrName } );
			} );
			const productInfo = {
				title: productTitle,
				href: productLink,
				image: productImage,
				sku: productSku === WCCON_BUILDER_FRONT.i18n_sku_na ? '' : productSku,
				stock_status: productStockStatus.hasClass( 'outofstock' ) ? 'outofstock' : 'instock',
				price: productPrice,
				quantity: 1,
				product_id: productId,
				multiple: componentMultiple,
				variable: productAttributes.length,
				attributes: productAttributesData,
			};
			if ( productData && this.soldIndividually ) {
				productInfo.sold_individually = productData.sold_individually ?? false;
			}
			const productFilled = wp.hooks.applyFilters(
				'wccon-choose-products-args',
				productInfo,
				productItem,
				componentContainer
			);
			// console.log('FILLED',productFilled);
			productBodyContainer.replaceWith( templateSelected( productFilled ) );

			const cloneIndex = _thisButton.closest( '.wccon-component' ).attr( 'data-copy' );

			const productValue = {
				product_id: productId,
				quantity: 1,
				variation_id: variationId ?? '',
				variation: '',
				price: productPriceRaw,
				clone: cloneIndex ? cloneIndex : 0,
				component: productSlug,
			};
			console.log( 'VALUE', productValue, productSlug, this.productScheme.groups );

			const newProductScheme = wccon_change_component(
				productSlug,
				this.productScheme.groups,
				productValue,
				'add'
			);
			console.log( 'DATASTORE', newProductScheme );
			this.productScheme = {
				...this.productScheme,
				groups: newProductScheme,
			};
			this.dataStore = newProductScheme;

			this.maybeSaveToStorage();

			this.totalPrice = this.calculateTotals();
			this.totalPriceField.html( wccon_price( this.totalPrice ) );

			//close window.

			const componentHead = componentContainer.find( '.wccon-component-inner' );
			$( componentHead ).removeClass( 'opened' );
			$( componentContainer ).removeClass( 'opened' );
			$( document.body ).removeClass( 'wccon-component-opened' );
			$( componentHead ).siblings( '.wccon-product-list' ).remove();

			//unsubscribe trap focus.
			wcconTrapFocus( componentContainer, 'off' );

			//check if this is subcomponent
			const subgroupContainer = componentContainer.parent( '.wccon-component' );
			if ( subgroupContainer.length ) {
				const subGroupSlug = subgroupContainer.attr( 'data-component-slug' );
				subgroupContainer.addClass( 'selected' );
				const subGroupProducts = wccon_get_components_products( subGroupSlug, this.productScheme.groups );
				console.log( 'SUB', subGroupProducts, subGroupSlug );
				const fullSubGroupProducts = subGroupProducts
					.map( ( product ) => {
						const subGroupComponents = $( `[data-component-slug=${ product.component }]` );
						const productContainer = $(
							`.wccon-component-product[data-product-id=${ product.product_id }]`
						);
						if ( subGroupComponents.length && productContainer.length ) {
							const subGroupTitle = productContainer.find( '.wccon-component-product__title' ).text();
							const subGroupImage = productContainer.find( '.wccon-component-product__image' ).html();
							return {
								...product,
								title: subGroupTitle,
								image: subGroupImage,
							};
						}
						return null;
					} )
					.filter( ( el ) => el !== null );

				const subTemplateSelected = wp.template( 'wccon-filled-subgroup' );
				const subBodyContainer = subgroupContainer
					.find( '.wccon-component-inner .wccon-component__body' )
					.first();
				subBodyContainer.siblings( '.wccon-component__icon' ).hide();

				subBodyContainer.replaceWith(
					subTemplateSelected( {
						products: fullSubGroupProducts,
					} )
				);
			}
			this.totalsContainer.show();
			this.builderWrapper.trigger( 'wccon-product-chosen', [
				productSlug,
				productId,
				this.dataStore,
				this.builderWrapper,
			] );
			//only if compatibility enabled.
			if ( this.compatiblityEnabled && WCCON_BUILDER_FRONT.nonce2 ) {
				if ( this.compatibilityRequest !== null ) {
					this.compatibilityRequest.abort();
					this.compatibilityRequest = null;
				}
				this.compatibilityRequest = $.ajax( {
					url: this.ajaxUrl,
					type: 'POST',
					data: {
						action: 'wccon_add_product',
						component_slug: productSlug,
						data: JSON.stringify( this.dataStore ),
						product: productValue,
						nonce: this.nonce,
					},
				} );
				this.compatibilityRequest
					.then( ( res ) => {
						console.log( res );
						this.runCompatibility( res.data.cd );
					} )
					.catch( ( err ) => {
						console.log( err );
					} );
			} else {
				this.runCompatibility( {} );
			}
		}
		removeProduct( e ) {
			e.preventDefault();
			const _thisButton = $( e.currentTarget );
			const productBodyContainer = _thisButton.closest( '.wccon-component__body' );
			const componentContainer = _thisButton.closest( '.wccon-component' );
			const isExtraComponent = componentContainer.hasClass( 'extra' );
			componentContainer.removeClass( 'selected out-ofstock' );
			const componentIcon = _thisButton.closest( '.wccon-component-inner' ).find( '.wccon-component__icon' );
			componentIcon.show();
			const productId = _thisButton
				.closest( '.wccon-component-product-item' )
				.find( '.wccon-component-product' )
				.attr( 'data-product-id' );

			//check if this is copy component
			const cloneIndex = _thisButton.closest( '.wccon-component' ).attr( 'data-copy' );
			const productSlug = _thisButton.closest( '.wccon-component' ).attr( 'data-component-slug' );

			const hasMultipleContainers = $( `[data-component-slug=${ productSlug }]` ).length > 1;
			const subgroupContainer = componentContainer.parent( '.wccon-component' );
			if ( cloneIndex || ( ! cloneIndex && hasMultipleContainers ) ) {
				componentContainer.remove();
			} else {
				const templateSelected = wp.template( 'wccon-empty-component' );
				const productEmpty = {};
				const compIcon = _thisButton.closest( '.wccon-component' ).find( '.wccon-component__selected' );

				compIcon.find( 'svg' ).html( '<use xlink:href="#icon-check"></use>' );
				productBodyContainer.replaceWith( templateSelected( productEmpty ) );
			}
			//recalculte copy index. This won't affect further wccon_change_component()

			$( `[data-component-slug=${ productSlug }]` ).each( function ( index, el ) {
				$( this ).attr( 'data-copy', function ( i, val ) {
					if ( index === 0 ) {
						$( this ).removeAttr( 'data-copy' );
						return;
					}
					return index;
				} );
			} );
			console.log( this.productScheme.groups, cloneIndex );
			const newProductScheme = wccon_change_component(
				productSlug,
				this.productScheme.groups,
				{ product_id: productId, clone: cloneIndex ? cloneIndex : 0 },
				'remove'
			);
			console.log( 'DATASTORE', newProductScheme );
			this.productScheme = {
				...this.productScheme,
				groups: newProductScheme,
			};
			this.dataStore = newProductScheme;

			this.maybeSaveToStorage();

			this.totalPrice = this.calculateTotals();
			this.totalPriceField.html( wccon_price( this.totalPrice ) );

			//check if this is subcomponent

			if ( subgroupContainer.length ) {
				const subGroupSlug = subgroupContainer.attr( 'data-component-slug' );
				const subGroupProducts = wccon_get_components_products( subGroupSlug, this.productScheme.groups );
				console.log( 'SUB', subGroupProducts, subGroupSlug );
				const fullSubGroupProducts = subGroupProducts
					.map( ( product ) => {
						const subGroupComponents = $( `[data-component-slug=${ product.component }]` );
						const productContainer = $(
							`.wccon-component-product[data-product-id=${ product.product_id }]`
						);
						if ( subGroupComponents.length && productContainer.length ) {
							const subGroupTitle = productContainer.find( '.wccon-component-product__title' ).text();
							const subGroupImage = productContainer.find( '.wccon-component-product__image' ).html();
							return {
								...product,
								title: subGroupTitle,
								image: subGroupImage,
							};
						}
						return null;
					} )
					.filter( ( el ) => el !== null );

				const subTemplateSelected = wp.template( 'wccon-filled-subgroup' );

				const subBodyContainer = subgroupContainer
					.find( '.wccon-component-inner .wccon-component__body' )
					.first();
				if ( ! fullSubGroupProducts.length ) {
					subgroupContainer.removeClass( 'selected' );
					subBodyContainer.siblings( '.wccon-component__icon' ).show();
					const subTemplateEmpty = wp.template( 'wccon-empty-subgroup' );
					subBodyContainer.replaceWith( subTemplateEmpty( {} ) );
				} else {
					subBodyContainer.siblings( '.wccon-component__icon' ).hide();

					subBodyContainer.replaceWith(
						subTemplateSelected( {
							products: fullSubGroupProducts,
						} )
					);
				}
			}

			// extra components
			if ( isExtraComponent ) {
				const belongsToGroup = componentContainer.parents( '.wccon-group' );
				if ( ! belongsToGroup.find( '.wccon-component-extra' ).hasClass( 'opened' ) ) {
					componentContainer.addClass( 'exhide' );
				}
			}
			this.builderWrapper.trigger( 'wccon-product-removed', [
				productSlug,
				productId,
				this.dataStore,
				this.builderWrapper,
			] );

			//only if compatibility enabled
			if ( this.compatiblityEnabled && WCCON_BUILDER_FRONT.nonce2 ) {
				if ( this.compatibilityRequest !== null ) {
					this.compatibilityRequest.abort();
					this.compatibilityRequest = null;
				}
				this.compatibilityRequest = $.ajax( {
					url: this.ajaxUrl,
					type: 'POST',
					data: {
						action: 'wccon_remove_product',
						component_slug: productSlug,
						data: JSON.stringify( this.dataStore ),
						nonce: this.nonce,
					},
				} );
				this.compatibilityRequest
					.then( ( res ) => {
						console.log( res );

						this.runCompatibility( res.data.cd );
					} )
					.catch( ( err ) => {
						console.log( err );
					} );
			} else {
				this.runCompatibility( {} );
			}
		}
		runCompatibility( data ) {
			
			if ( ! this.compatiblityEnabled || ! WCCON_BUILDER_FRONT.nonce2 ) {
				$( '.wccon-component', this.builderWrapper )
					.not( '.wccon-component-has-children' )
					.each( function () {
						if ( $( this ).is( '.selected' ) ) {
							$( this ).find( '.wccon-component__selected' ).addClass( 'compatible' );
						} else {
							$( this ).find( '.wccon-component__selected' ).removeClass( 'compatible' );
						}
					} );
				$( '.wccon-component', this.builderWrapper )
					.filter( '.wccon-component-has-children' )
					.each( function () {
						if ( $( this ).find( '.wccon-component.selected' ).length ) {
							$( '> .wccon-component-inner', $( this ) )
								.find( '.wccon-component__selected' )
								.addClass( 'compatible' );
						} else {
							$( '> .wccon-component-inner', $( this ) )
								.find( '.wccon-component__selected' )
								.removeClass( 'compatible' );
						}
					} );
					
				return;
			}
			$( '.wccon-component__selected', this.builderWrapper ).removeClass( 'incompatible compatible' );
			if ( $( '.product-top__required-item', this.builderWrapper ).length ) {
				$( '.product-top__required-item', this.builderWrapper ).removeClass( 'incompatible compatible' );
			}
			if ( $( '.product-top__extra-item', this.builderWrapper ).length ) {
				$( '.product-top__extra-item', this.builderWrapper ).removeClass( 'incompatible compatible' );
			}

			this.builderWrapper.trigger( 'wccon-run-compatibility', [ this, data ] );

			this.incompatibleData = []; //clear data before storing new values.
			const incompatibleProducts = Object.values( data );
			console.log( 'Incompatibility products:', incompatibleProducts );
			for ( let icpProduct of incompatibleProducts ) {
				let foundComponent;
				if ( parseInt( icpProduct.clone, 10 ) > 0 ) {
					foundComponent = $(
						`[data-component-slug=${ icpProduct.slug }][data-copy=${ parseInt( icpProduct.clone, 10 ) }]`,
						this.builderWrapper
					).first();
				} else {
					foundComponent = $( `[data-component-slug=${ icpProduct.slug }]`, this.builderWrapper )
						.not( '[data-copy]' )
						.first();
				}
				const selectedIcon = foundComponent.find( '.wccon-component__selected' );

				if ( icpProduct.compatible ) {
					selectedIcon.addClass( 'compatible' );
					selectedIcon.find( 'svg' ).html( '<use xlink:href="#icon-check"></use>' );
					$( `.product-top__required-item[data-image-slug="${ icpProduct.slug }"]` ).addClass( 'compatible' );
					$( `.product-top__extra-item[data-image-slug="${ icpProduct.slug }"]` ).addClass( 'compatible' );
				} else {
					this.incompatibleData = [
						...this.incompatibleData,
						{
							slug: icpProduct.slug,
							clone: icpProduct.clone,
							text: icpProduct.tippyText,
						},
					];
					selectedIcon.addClass( 'incompatible' );
					selectedIcon.find( 'svg' ).html( '<use xlink:href="#icon-uncheck"></use>' );
					$( `.product-top__required-item[data-image-slug="${ icpProduct.slug }"]` ).addClass(
						'incompatible'
					);
					$( `.product-top__extra-item[data-image-slug="${ icpProduct.slug }"]` ).addClass( 'incompatible' );

					let displayCompatibilityAlerts = true;
					if ( this.isMobile ) {
						displayCompatibilityAlerts = false;
					}
					const compAlertsFiltered = wp.hooks.applyFilters(
						'wccon-display-compatibility-alerts',
						displayCompatibilityAlerts
					);
					if ( compAlertsFiltered ) {
						toastr.error( icpProduct.text );
					}
				}
			}
			//subgroup update icon.
			$( '.wccon-component-has-children', this.builderWrapper ).each( ( i, el ) => {
				let compatible = true;
				let initCompatible = false;
				const compatibleArr = [];
				$( '.wccon-component', $( el ) ).each( function () {
					if ( $( this ).find( '.wccon-component__selected.compatible' ).length ) {
						initCompatible = true;
						compatibleArr.push( true );
					}
					if ( $( this ).find( '.wccon-component__selected.incompatible' ).length ) {
						initCompatible = true;
						compatible = false;
						compatibleArr.push( false );
					}
				} );
				const subGroupSlug = $( el ).attr( 'data-component-slug' );
				const subGroupIcon = $( '.wccon-component-inner', $( el ) )
					.first()
					.find( '.wccon-component__selected' );
				if ( initCompatible && compatible ) {
					subGroupIcon.addClass( 'compatible' );
					subGroupIcon.find( 'svg' ).html( '<use xlink:href="#icon-check"></use>' );
				} else if ( initCompatible && ! compatible ) {
					subGroupIcon.addClass( 'incompatible' );
					subGroupIcon.find( 'svg' ).html( '<use xlink:href="#icon-uncheck"></use>' );
				}
				if ( compatibleArr.length && compatibleArr.indexOf( true ) !== -1 ) {
					$( `.product-top__required-item[data-image-slug="${ subGroupSlug }"]` ).addClass( 'compatible' );
				} else if ( compatibleArr.length && compatibleArr.indexOf( true ) === -1 ) {
					$( `.product-top__required-item[data-image-slug="${ subGroupSlug }"]` ).addClass( 'incompatible' );
				}
			} );

		}

		runCompatibilitySaved() {
			if ( ! this.compatiblityEnabled ) {
				return;
			}
			$( '.wccon-component', this.builderWrapper ).each( function ( i, el ) {
				const tippyYes = $( el ).attr( 'data-tippy' );
				if ( tippyYes && tippyYes !== '' ) {
					const needeeEl = $( el ).find( '.wccon-component__selected' );
					if ( needeeEl.length ) {
						tippy( needeeEl.get( 0 ), {
							content: tippyYes,
							popperOptions: {
								modifiers: [ { name: 'eventListeners', enabled: false } ],
							},
						} );
					}
				}
			} );
			// tippy('[data-tippy]');
			// tippy(
			// 	`[data-component-slug="${ componentData.slug }"][data-copy="${ componentData.clone }"] .wccon-component__selected`,
			// 	{
			// 		content: componentData.text,
			// 	}
			// );
		}

		quantityButtonsHandler( e ) {}

		editProduct( e ) {
			const currentItem = e.currentTarget;
			const componentHead = $( currentItem ).closest( '.wccon-component-inner' );

			this.handleGetProducts( componentHead );
		}
		collapseProducts( e ) {
			e.preventDefault();
			e.stopPropagation();
			const currentItem = e.currentTarget;
			const componentHead = $( currentItem ).closest( '.wccon-component-inner' );
			const componentContainer = $( currentItem ).closest( '.wccon-component' );
			$( componentHead ).removeClass( 'opened' );
			componentContainer.removeClass( 'opened' );
			$( document.body ).removeClass( 'wccon-component-opened' );
			$( componentHead ).siblings( '.wccon-product-list' ).remove();

			//unsubscribe trap focus.
			wcconTrapFocus( componentContainer, 'off' );

			//if is clone whithout selected product yet
			if ( ! componentContainer.hasClass( 'selected' ) && componentContainer.attr( 'data-copy' ) ) {
				componentContainer.remove();
			}
			//abort request.
			if ( this.productRequest !== null ) {
				this.productRequest.abort();
				this.productRequest = null;
			}
			//show totals.
			this.totalsContainer.show();
		}

		buyProduct( e ) {
			e.preventDefault();
			const currentItem = e.currentTarget;

			this.builderWrapper.trigger( 'wccon-before-adding-to-cart', [ this ] );

			$( currentItem ).attr( 'disabled', true );
			$( currentItem ).addClass( 'loading' );

			$.ajax( {
				url: this.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wccon_buy_product',
					data: JSON.stringify( this.dataStore ),
					nonce: this.nonce,
				},
				beforeSend: () => {
					$( document.body ).trigger( 'adding_to_cart' );
				},
			} )
				.then( ( res ) => {
					console.log( res );
					if ( res.success && res.data.fragments ) {
						const $supports_html5_storage = 'sessionStorage' in window && window.sessionStorage !== null;

						if ( typeof window.wc_cart_fragments_params !== 'undefined' ) {
							const cart_hash_key = wc_cart_fragments_params.cart_hash_key;
							$.each( res.data.fragments, function ( key, value ) {
								$( key ).replaceWith( value );
							} );

							if ( $supports_html5_storage ) {
								sessionStorage.setItem(
									wc_cart_fragments_params.fragment_name,
									JSON.stringify( res.data.fragments )
								);

								localStorage.setItem( cart_hash_key, res.data.cart_hash );
								sessionStorage.setItem( cart_hash_key, res.data.cart_hash );

								if ( res.data.cart_hash ) {
									sessionStorage.setItem( 'wc_cart_created', new Date().getTime() );
								}
							}
						}

						$( document.body ).trigger( 'wc_fragments_refreshed' );
						$( document.body ).trigger( 'wccon_buy_list_success', [ res.data ] );
						$( currentItem ).attr( 'disabled', false );
						$( currentItem ).removeClass( 'loading' );
					}
				} )
				.catch( ( err ) => {
					console.log( err );
					$( document.body ).trigger( 'wccon_buy_list_error' );
					$( document.body ).trigger( 'wc_fragments_ajax_error' );
				} );
		}
		calculateTotals( data = this.dataStore ) {
			let total = 0;
			const _this = this;

			for ( const group of data ) {
				if ( group.hasOwnProperty( 'products' ) ) {
					total = group.products.reduce( function ( acc, item ) {
						let itemPrice = 0;
						let itemQuantity = 1;
						if ( item.hasOwnProperty( 'price' ) && item.price !== '' ) {
							itemPrice = _this.maybeStringToNumber( item.price );
							itemQuantity = _this.maybeStringToNumber( item.quantity, false );
						}

						return acc + itemPrice * itemQuantity;
					}, total );
					// if ( group.hasOwnProperty( 'price' ) ) {
					// 	 += parseFloat( group.price );
					// }
				}

				if ( group.hasOwnProperty( 'components' ) ) {
					total += this.calculateTotals( group.components );
				}
			}

			return total;
		}
		chooseVariation( product ) {
			const variationData = product.data( 'product_variations' );
			const stockEl = product.find( '.stock' );
			const attributes = this.getChosenAttributes( product ),
				currentAttributes = attributes.data;
			const matchingVariations = this.findMatchingVariations( variationData, currentAttributes ),
				variation = matchingVariations.shift();
			// console.log( attributes, currentAttributes, variation );
			const productPrice = product.find( '.price' );
			const productSku = product.find( '.wccon-product-sku span' );
			const productStock = product.find( '.stock' );
			const productImage = product.find( '.product-item__image img' );

			//for find first match variation.
			const firstMatch = wp.hooks.applyFilters( 'wccon-variation-first-match', false );
			if ( attributes.count && firstMatch ) {
				this.updateVariations( 'firstmatch' ); //TODO
			}
			if ( attributes.count && attributes.count === attributes.chosenCount ) {
				if ( variation ) {
					if ( attributes.chosenCount ) {
						this.wccon_set_content(
							productPrice,
							variation.price_html ? variation.price_html : variation.range_html
						);
						this.wccon_set_content(
							productSku,
							variation.sku ? variation.sku : WCCON_BUILDER_FRONT.i18n_sku_na
						);
						this.wccon_set_content( productStock, variation.stock_html );

						if ( variation.image && variation.image.url && variation.image.url.length > 0 ) {
							this.wccon_set_variation_attr( productImage, 'src', variation.image.url );
						}
						product.attr( 'data-variation-id', variation.variation_id );
						product.attr( 'data-price', variation.display_price );

						//check if no-stock
						if ( variation.is_in_stock ) {
							product.find( '.product-item__add' ).attr( 'disabled', false );
							stockEl.removeClass( 'outofstock' ).addClass( 'instock' );
							stockEl.html( variation.stock_html );
						} else {
							product.find( '.product-item__add' ).attr( 'disabled', true );
							stockEl.removeClass( 'instock' ).addClass( 'outofstock' );
							stockEl.html( variation.nostock_html );
						}

						//check if variation already selected
						const builderWrapper = product.closest( '.wccon-builder-wrapper' );
						const alreadySelectedVariation = builderWrapper.find(
							`.wccon-component-product[data-product-id="${ variation.variation_id }"]`
						);
						if ( alreadySelectedVariation.length ) {
							product.find( '.product-item__add' ).attr( 'disabled', true );
						}
					} else {
						this.wccon_set_content( productPrice, variation.range_price );
						this.wccon_set_content(
							productSku,
							variation.sku ? variation.sku : WCCON_BUILDER_FRONT.i18n_sku_na
						);
						this.wccon_set_content( productStock, variation.stock_html );
						if ( variation.image && variation.image.url && variation.image.url.length > 0 ) {
							this.wccon_set_variation_attr( productImage, 'src', variation.image.url );
						}
						product.attr( 'data-variation-id', '' );
						product.attr( 'data-price', '' );
						product.find( '.product-item__add' ).attr( 'disabled', true );
					}
				} else {
					attributes.chosenCount = 0;
					product.find( '.product-item__add' ).attr( 'disabled', true );
				}
			} else {
				attributes.chosenCount = 0;
				product.find( '.product-item__add' ).attr( 'disabled', true );
			}
			this.updateVariations();
		}
		updateVariations() {
			const variationProducts = $( '.wccon-product-item', this.builderWrapper ).filter( ( i, el ) => {
				const hasAttr = $( el ).attr( 'data-product_variations' );

				return hasAttr;
			} );
			// console.log( variationProducts );
			variationProducts.each( ( i, product ) => {
				const variationData = $( product ).data( 'product_variations' );
				const attributes = this.getChosenAttributes( $( product ) ),
					currentAttributes = attributes.data;

				// console.log( product );
				$( product )
					.find( 'select[name^=attribute_]' )
					.each( ( index, el ) => {
						//standart select
						const standartSelect = $( el ).closest( '.product-attribute-group__st-select' );
						const currentAttributeName = $( el ).data( 'attribute_name' );
						const checkAttributes = $.extend( true, {}, currentAttributes );

						checkAttributes[ currentAttributeName ] = '';
						const variations = this.findMatchingVariations( variationData, checkAttributes );
						console.log( 'VARIATIONS', variations );
						if ( standartSelect.length ) {
							let current_attr_select = $( el ),
								show_option_none = $( el ).data( 'show_option_none' ),
								option_gt_filter = ':gt(0)',
								attached_options_count = 0,
								new_attr_select = $( '<select/>' ),
								selected_attr_val = current_attr_select.val() || '',
								selected_attr_val_valid = true;

							// Reference options set at first.
							if ( ! current_attr_select.data( 'attribute_html' ) ) {
								var refSelect = current_attr_select.clone();

								refSelect
									.find( 'option' )
									.removeAttr( 'attached' )
									.prop( 'disabled', false )
									.prop( 'selected', false );

								// Legacy data attribute.
								current_attr_select.data(
									'attribute_options',
									refSelect.find( 'option' + option_gt_filter ).get()
								);
								current_attr_select.data( 'attribute_html', refSelect.html() );
							}

							new_attr_select.html( current_attr_select.data( 'attribute_html' ) );

							// The attribute of this select field should not be taken into account when calculating its matching variations:
							// The constraints of this attribute are shaped by the values of the other attributes.

							// console.log( 'VARIATIONS', variations );
							// Loop through variations.
							for ( var num in variations ) {
								if ( typeof variations[ num ] !== 'undefined' ) {
									var variationAttributes = variations[ num ].attributes;

									for ( var attr_name in variationAttributes ) {
										if ( variationAttributes.hasOwnProperty( attr_name ) ) {
											var attr_val = variationAttributes[ attr_name ],
												variation_active = '';

											if ( attr_name === currentAttributeName ) {
												if ( variations[ num ].variation_is_active ) {
													variation_active = 'enabled';
												}

												if ( attr_val ) {
													// Decode entities.
													attr_val = $( '<div/>' ).html( attr_val ).text();

													// Attach to matching options by value. This is done to compare
													// TEXT values rather than any HTML entities.
													var $option_elements = new_attr_select.find( 'option' );
													if ( $option_elements.length ) {
														for ( var i = 0, len = $option_elements.length; i < len; i++ ) {
															var $option_element = $( $option_elements[ i ] ),
																option_value = $option_element.val();

															if ( attr_val === option_value ) {
																$option_element.addClass(
																	'attached ' + variation_active
																);
																break;
															}
														}
													}
												} else {
													// Attach all apart from placeholder.
													new_attr_select
														.find( 'option:gt(0)' )
														.addClass( 'attached ' + variation_active );
												}
											}
										}
									}
								}
							}

							// Count available options.
							attached_options_count = new_attr_select.find( 'option.attached' ).length;

							// Check if current selection is in attached options.
							if ( selected_attr_val ) {
								selected_attr_val_valid = false;

								if ( 0 !== attached_options_count ) {
									new_attr_select.find( 'option.attached.enabled' ).each( function () {
										var option_value = $( this ).val();

										if ( selected_attr_val === option_value ) {
											selected_attr_val_valid = true;
											return false; // break.
										}
									} );
								}
							}

							if (
								attached_options_count > 0 &&
								selected_attr_val &&
								selected_attr_val_valid &&
								'no' === show_option_none
							) {
								new_attr_select.find( 'option:first' ).remove();
								option_gt_filter = '';
							}

							// Detach unattached.
							new_attr_select.find( 'option' + option_gt_filter + ':not(.attached)' ).remove();

							// Finally, copy to DOM and set value.
							current_attr_select.html( new_attr_select.html() );
							current_attr_select
								.find( 'option' + option_gt_filter + ':not(.enabled)' )
								.prop( 'disabled', true );

							// Choose selected value.
							if ( selected_attr_val ) {
								// If the previously selected value is no longer available, fall back to the placeholder (it's going to be there).
								if ( selected_attr_val_valid ) {
									current_attr_select.val( selected_attr_val );
								} else {
									current_attr_select.val( '' ).trigger( 'change' );
								}
							} else {
								current_attr_select.val( '' ); // No change event to prevent infinite loop.
							}
						}

						const availableValues = this.findVariationValue( variations, currentAttributeName );
						//console.log( 'available', availableValues );
						//nice-select
						const niceSelectEl = $( el ).closest( '.product-attribute-group__nice-select' );

						//color select
						const colorSelectEl = $( el ).closest( '.product-attribute-group__color' );

						//button select
						const buttonSelectEl = $( el ).closest( '.product-attribute-group__button' );

						if ( niceSelectEl.length ) {
							const newSelectOptions = niceSelectEl.find( 'select option' );
							const newSelectVal = niceSelectEl.find( 'select' ).val();
							const niceSelectList = niceSelectEl.find( 'ul' );
							const niceSelectListItems = niceSelectList.find( 'li' );
							const attrNameSpan = niceSelectEl.find( '.product-attribute-selected span' );
							niceSelectListItems.removeClass( 'disabled' );

							niceSelectListItems.each( ( i, item ) => {
								const liValue = $( item ).attr( 'data-value' );
								const ariaSelected = newSelectVal === liValue ? 'true' : 'false';
								$( item ).removeClass( 'disabled' );
								// const attrValues = this.findVariationValue( variations, currentAttributeName, liValue );
								if ( liValue !== '' && availableValues.indexOf( liValue ) === -1 ) {
									$( item ).addClass( 'disabled' );
								}
								if ( ariaSelected === 'true' ) {
									attrNameSpan.text( $( item ).text() );
								}
							} );
						}
						if ( colorSelectEl.length ) {
							const newSelecColortOptions = colorSelectEl.find( 'select option' );
							const newSelectColorVal = colorSelectEl.find( 'select' ).val();

							const colorList = colorSelectEl.find( 'ul' );
							const colorListItems = colorList.find( 'li' );
							colorListItems.removeClass( 'disabled' );

							colorListItems.each( ( i, item ) => {
								const liValue = $( item ).attr( 'data-value' );
								const ariaSelected = newSelectColorVal === liValue ? 'true' : 'false';
								$( item ).removeClass( 'disabled' );
								// const attrValues = this.findVariationValue( variations, currentAttributeName, liValue );
								if ( liValue !== '' && availableValues.indexOf( liValue ) === -1 ) {
									$( item ).addClass( 'disabled' );
								}

								$( item ).attr( 'aria-checked', ariaSelected );
							} );
						}

						if ( buttonSelectEl.length ) {
							const newSelecButtontOptions = buttonSelectEl.find( 'select option' );
							const newSelectButtonVal = buttonSelectEl.find( 'select' ).val();
							const buttonList = buttonSelectEl.find( 'ul' );
							const buttonListItems = buttonList.find( 'li' );
							buttonListItems.removeClass( 'disabled' );

							buttonListItems.each( ( i, item ) => {
								const liValue = $( item ).attr( 'data-value' );
								const ariaSelected = newSelectButtonVal === liValue ? 'true' : 'false';
								$( item ).removeClass( 'disabled' );
								// const attrValues = this.findVariationValue( variations, currentAttributeName, liValue );
								// console.log(attrValues,liValue);
								if ( liValue !== '' && availableValues.indexOf( liValue ) === -1 ) {
									$( item ).addClass( 'disabled' );
								}

								$( item ).attr( 'aria-checked', ariaSelected );
							} );
						}
					} );
			} );
		}
		findVariationValue( variations, currentAttributeName, liValue ) {
			const variationValues = [];
			// Loop through variations.
			for ( const varIndex in variations ) {
				if ( typeof variations[ varIndex ] !== 'undefined' ) {
					const variationAttributes = variations[ varIndex ].attributes;
					// console.log(variationAttributes);
					for ( const attrName in variationAttributes ) {
						if ( variationAttributes.hasOwnProperty( attrName ) ) {
							const attrVal = variationAttributes[ attrName ];

							if ( attrName === currentAttributeName ) {
								if ( attrVal ) {
								}
								variationValues.push( attrVal );
							}
						}
					}
				}
			}
			return variationValues;
		}
		resetVariations( e ) {
			e.preventDefault();
			const currentLink = $( e.currentTarget );
			const productEl = currentLink.closest( '.wccon-product-item' );
			const productSku = productEl.find( '.wccon-product-sku span' );
			const productPrice = productEl.find( '.price' );
			const productStock = productEl.find( '.stock' );
			const productImage = productEl.find( '.product-item__image img' );
			productEl.find( 'select[name^=attribute_]' ).val( '' ).trigger( 'change' );
			const niceSelectEls = productEl.find( '.product-attribute-group__nice-select' );

			if ( niceSelectEls.length ) {
				niceSelectEls.each( function ( i, item ) {
					const firstOption = $( item ).find( 'select[name^=attribute_] option:eq(0)' );
					$( item ).find( '.product-attribute-selected span' ).text( firstOption.text() );
				} );
			}
			this.wccon_reset_content( productSku );
			this.wccon_reset_content( productPrice );
			this.wccon_reset_content( productStock );
			this.wccon_reset_variation_attr( productImage, 'src' );
			if ( productStock.html().includes( '#icon-chk-small' ) ) {
				productStock.removeClass( 'outofstock' ).addClass( 'instock' );
			} else {
				productStock.removeClass( 'instock' ).addClass( 'outofstock' );
			}
			// console.log( productEl.find( 'select[name^=attribute_]' ) );
			productEl.trigger( 'reset_data' );
		}

		findMatchingVariations( variations, attributes ) {
			const matching = [];
			for ( let i = 0; i < variations.length; i++ ) {
				const variation = variations[ i ];

				if ( this.isMatch( variation.attributes, attributes ) ) {
					matching.push( variation );
				}
			}
			return matching;
		}

		isMatch( variationAttributes, attributes ) {
			let match = true;
			for ( const attrName in variationAttributes ) {
				if ( variationAttributes.hasOwnProperty( attrName ) ) {
					const val1 = variationAttributes[ attrName ];
					const val2 = attributes[ attrName ];
					if (
						val1 !== undefined &&
						val2 !== undefined &&
						val1.length !== 0 &&
						val2.length !== 0 &&
						val1 !== val2
					) {
						match = false;
					}
				}
			}
			return match;
		}
		getChosenAttributes( product ) {
			const data = {};
			let count = 0;
			let chosen = 0;
			const attributeFields = product.find( 'select[name^=attribute_]' );
			// console.log( attributeFields );
			attributeFields.each( function () {
				const attributeName = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );

				const value = $( this ).val() || '';

				if ( value.length > 0 ) {
					chosen++;
				}

				count++;
				data[ attributeName ] = value;
			} );

			return {
				count,
				chosenCount: chosen,
				data,
			};
		}
		addMultiple( e ) {}
		handleExpandDescription( e ) {
			e.preventDefault();
			e.stopPropagation();

			const currentItem = e.currentTarget;
			const descriptionContainer = $( currentItem )
				.closest( '.wccon-product-title' )
				.siblings( '.product-item__desc' );
			descriptionContainer.toggleClass( 'active' );
			$( currentItem ).toggleClass( 'active' );
		}
		maybeGetProducts( e ) {
			const currentItem = e.currentTarget;
			const componentItem = $( currentItem ).closest( '.wccon-component' );
			console.log( 'ATTEMP get products' );
			if ( componentItem.hasClass( 'wccon-component-has-children' ) ) {
				componentItem.toggleClass( 'subgroup-opened' );
				return;
			}
			if ( componentItem.hasClass( 'selected' ) ) {
				return;
			}
			if ( ! $( currentItem ).hasClass( 'opened' ) ) {
				this.builderWrapper
					.find( '.wccon-component' )
					.not( componentItem )
					.each( ( i, el ) => {
						$( el ).removeClass( 'opened' );
						$( el ).find( '.wccon-component-inner' ).removeClass( 'opened' );
						$( el ).find( '.wccon-product-list' ).remove();
					} );

				//abort request.
				if ( this.productRequest !== null ) {
					this.productRequest.abort();
					this.productRequest = null;
				}

				//show totals.
				this.totalsContainer.show();
			}
			if ( $( e.target ).closest( '.wccon-component-product-item' ).length ) {
				return;
			}
			if ( $( currentItem ).hasClass( 'opened' ) ) {
				$( currentItem ).removeClass( 'opened' );
				componentItem.removeClass( 'opened' );
				$( currentItem ).siblings( '.wccon-product-list' ).remove();
				$( document.body ).removeClass( 'wccon-component-opened' );

				//unsubscribe trap focus.
				wcconTrapFocus( componentItem, 'off' );

				//if is clone -- remove
				if ( ! componentItem.hasClass( 'selected' ) && componentItem.attr( 'data-copy' ) ) {
					componentItem.remove();
				}

				//abort request.
				if ( this.productRequest !== null ) {
					this.productRequest.abort();
					this.productRequest = null;
				}
				//show totals.
				this.totalsContainer.show();
				return;
			} else if ( componentItem.hasClass( 'selected' ) ) {
				return;
			}

			this.handleGetProducts( currentItem );
		}

		/**
		 * Get products by ajax.
		 * @param {jQuery Object} currentItem .wccon-component-inner
		 */
		handleGetProducts( currentItem ) {
			const componentItem = $( currentItem ).closest( '.wccon-component' );
			const isExtraComponent = componentItem.hasClass( 'extra' );
			let componentName = 'component_id';
			if ( $( currentItem ).siblings( '.wccon-component' ).length ) {
				componentName = 'subgroup_id';
			}
			const componentId = componentItem.data( 'component-id' );
			const componentSlug = componentItem.data( 'component-slug' );
			this.componentId = componentId;
			this.componentSlug = componentSlug;
			wccon_generate_skeleton_builder( currentItem, 5 );
			$( currentItem ).addClass( 'opened' );

			componentItem.addClass( 'opened' );
			$( 'html, body' ).animate( { scrollTop: $( currentItem ).offset().top }, 200 );
			$( document.body ).addClass( 'wccon-component-opened' );

			//subscribe trap focus. While waiting products.
			wcconTrapFocus( componentItem, 'on' );

			//custom event on component opened
			this.builderWrapper.trigger( 'wccon-component-opened', [ componentSlug ] );

			const componentProducts = wccon_get_components_products( componentSlug, this.productScheme.groups );
			console.log( 'component products:', componentProducts );
			//hide totals.
			this.totalsContainer.hide();
			// return;
			if ( this.productRequest !== null ) {
				this.productRequest.abort();
				this.productRequest = null;
			}
			this.productRequest = $.ajax( {
				url: this.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wccon_component_products',
					[ componentName ]: componentId,
					extra: isExtraComponent,
					products: componentProducts,
					nonce: this.nonce,
				},
			} );
			this.productRequest
				.then( ( res ) => {
					console.log( res );
					if ( res.success ) {
						//remove skeleton.
						$( currentItem ).siblings( '.wccon-product-list' ).remove();

						//insert ajax items.
						$( res.data.html ).insertAfter( $( currentItem ) );
						this.builderWrapper.trigger( 'wccon-products-loaded', [ componentSlug ] );

						//resubscribe trap focus.
						wcconTrapFocus( componentItem, 'off' );
						const componentItemNew = $( currentItem ).closest( '.wccon-component' );
						wcconTrapFocus( componentItemNew, 'on' );
					}
				} )
				.catch( ( err ) => console.log( err ) );
		}
		saveList( e ) {
			e.preventDefault();
			let savelistId = 0;
			if ( this.isListExists() ) {
				savelistId = this.getListId();
			}
			const currentItem = $( e.currentTarget ).closest( 'button' );
			currentItem.attr( 'disabled', true );
			currentItem.addClass( 'loading' );
			console.log( 'Product scheme:', this.productScheme );
			$.ajax( {
				url: this.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wccon_save_list',
					data: JSON.stringify( this.productScheme ),
					id: this.builderId,
					saved_list_id: savelistId,
					nonce: this.nonce,
				},
			} )
				.then( ( res ) => {
					console.log( res );
					if ( res.success ) {
						toastr.success( this.successMessage );
					} else {
						const returnMessage = res.data.message ? res.data.message : this.errorMessage;
						toastr.error( returnMessage );
					}
					currentItem.attr( 'disabled', false );
					currentItem.removeClass( 'loading' );
				} )
				.catch( ( err ) => {
					console.log( err );
					toastr.error( this.errorMessage );
				} );
		}
		clearList( e ) {
			e.preventDefault();
			this.productScheme = {
				...this.productScheme,
				groups: wccon_clear_products( this.builderWrapperData.groups ),
			}; ///REMOVE HERE PRODUCTS
			this.dataStore = this.productScheme.groups;
			this.maybeSaveToStorage();

			this.totalPrice = this.calculateTotals();
			this.totalPriceField.html( wccon_price( this.totalPrice ) );
			const allComponents = $( '.wccon-component[data-component-slug]', this.builderWrapper );

			const templateSelected = wp.template( 'wccon-empty-component' );
			const subTemplateEmpty = wp.template( 'wccon-empty-subgroup' );
			allComponents.each( ( i, el ) => {
				if ( $( el ).attr( 'data-copy' ) ) {
					$( el ).remove();
				}
				const productBodyContainer = $( el ).find( '.wccon-component__body' );
				const subgroupContainer = $( el ).hasClass( 'wccon-component-has-children' );
				$( el ).removeClass( 'selected' );
				const compIcon = $( el ).find( '.wccon-component__selected' );
				compIcon.find( 'svg' ).html( '<use xlink:href="#icon-check"></use>' );
				compIcon.removeClass( 'compatible incompatible' );
				if ( ! subgroupContainer ) {
					const productEmpty = {};

					productBodyContainer.siblings( '.wccon-component__icon' ).show();

					productBodyContainer.replaceWith( templateSelected( productEmpty ) );
				} else {
					const subBodyContainer = $( el ).find( '.wccon-component-inner .wccon-component__body' ).first();
					subBodyContainer.siblings( '.wccon-component__icon' ).show();

					subBodyContainer.replaceWith( subTemplateEmpty( {} ) );
				}
			} );

			this.builderWrapper.trigger( 'wccon-builder-cleared', [ this ] );
		}
		shareList( e ) {
			e.preventDefault();
			const shareBox = $( this ).parents( '.wccon-product-top__right' ).find( '.wccon-share-box' );
			shareBox.toggleClass( 'active' );
			if ( window.matchMedia( '(max-width: 768px)' ).matches ) {
				if ( $( this ).is( '.wccon-share-list-button' ) ) {
					$( '#wccon-share-modal' ).fadeIn( 400, function ( el ) {
						$( this ).css( 'display', 'flex' );
					} );
				} else {
					$( '#wccon-share-modal' ).fadeOut( 400, function ( el ) {
						$( this ).css( 'display', 'none' );
					} );
				}
			} else {
				if ( $( this ).is( '.wccon-share-list-button' ) ) {
					const scrlheight = shareBox.get( 0 ).scrollHeight;

					if ( ! shareBox.hasClass( 'jcstart' ) ) {
						shareBox.addClass( 'jcstart' );
						shareBox.outerHeight( scrlheight + 10 );
					} else {
						shareBox.outerHeight( scrlheight + 10 );
					}
				} else if ( $( this ).is( '.wccon-share-close' ) && shareBox.hasClass( 'jcstart' ) ) {
					shareBox.outerHeight( 0 );
				}
			}
		}

		async copyLink( e ) {
			e.preventDefault();
			const currentItem = $( e.currentTarget );
			const link = currentItem.attr( 'href' );

			if ( navigator.clipboard ) {
				try {
					await navigator.clipboard.writeText( link );

					toastr.success( this.copiedMessage );
				} catch ( err ) {
					console.error( 'Failed to copy: ', err );
				}
			} else {
				const textArea = $( '<input />', { type: 'text' } );
				textArea.val( link );
				textArea.css( 'position', 'absolute' );
				textArea.css( 'left', '-9999px' );
				$( document.body ).append( textArea );
				textArea.focus();
				textArea.select();
				document.execCommand( 'copy' );
				textArea.remove();
				toastr.success( this.copiedMessage );
			}
		}

		toggleWidget() {
			const widgetBody = $( this ).siblings( '.wccon-widget-body' );
			$( this ).toggleClass( 'active' );
			widgetBody.toggleClass( 'active' );
			if ( widgetBody.hasClass( 'active' ) ) {
				const bodyHeight = wp.hooks.applyFilters( 'wccon-widget-body-height', '220px' );
				widgetBody.css( 'max-height', bodyHeight );
				widgetBody.parent().addClass( 'opened' );
			} else {
				widgetBody.css( 'max-height', '0px' );

				widgetBody.parent().removeClass( 'opened' );
			}
		}
		openMyListsHandler( e ) {
			e.preventDefault();
			$( 'html' ).css( 'overflow', 'hidden' );
			const _this = this;
			$( '#wccon-user-list' ).fadeIn( 400, function ( el ) {
				$( this ).css( 'display', 'flex' );

				if ( $( '.wccon-saved-lists', $( '#wccon-user-list' ) ).length ) {
					wccon_resize_window_el( '#wccon-user-list' );
					return;
				}

				wccon_generate_skeleton_list( '#wccon-user-list .wccon-modal__body', 6 );
				wccon_resize_window_el( '#wccon-user-list' );
				$.ajax( {
					url: WCCON_BUILDER_FRONT.ajax_url,
					type: 'POST',
					data: {
						action: 'wccon_user_list',
						nonce: WCCON_BUILDER_FRONT.nonce,
						shortcode_id: $( _this ).closest( '.wccon-builder-wrapper' ).data( 'wccon-builder' ),
					},
				} ).then( ( res ) => {
					console.log( res );
					$( '#wccon-user-list' ).find( '.wccon-skeleton-item' ).remove();
					$( '.wccon-modal__body', $( '#wccon-user-list' ) ).html( res.data.html );

					//resize window
					wccon_resize_window_el( '#wccon-user-list' );
				} );
			} );
		}
		openUsersListsHandler( e ) {
			e.preventDefault();
			$( 'html' ).css( 'overflow', 'hidden' );
			const _this = this;
			$( '#wccon-users-list' ).fadeIn( 400, function ( el ) {
				$( this ).css( 'display', 'flex' );

				if ( $( '.wccon-saved-lists', $( '#wccon-users-list' ) ).length ) {
					wccon_resize_window_el( '#wccon-users-list' );
					return;
				}

				wccon_generate_skeleton_list( '#wccon-users-list .wccon-modal__body', 6 );
				wccon_resize_window_el( '#wccon-users-list' );
				$.ajax( {
					url: WCCON_BUILDER_FRONT.ajax_url,
					type: 'POST',
					data: {
						action: 'wccon_users_list',
						nonce: WCCON_BUILDER_FRONT.nonce,
						shortcode_id: $( _this ).closest( '.wccon-builder-wrapper' ).data( 'wccon-builder' ),
					},
				} ).then( ( res ) => {
					console.log( res );
					$( '#wccon-users-list' ).find( '.wccon-skeleton-item' ).remove();
					$( '.wccon-modal__body', $( '#wccon-users-list' ) ).html( res.data.html );

					//resize window
					wccon_resize_window_el( '#wccon-users-list' );
				} );
			} );
		}
		closeModal( e ) {
			e.preventDefault();
			$( 'html' ).css( 'overflow', 'initial' );
			$( this )
				.closest( '.wccon-modal' )
				.fadeOut( 400, function ( el ) {
					$( this ).css( 'display', 'none' );
				} );
		}
		openFilters( e ) {
			e.preventDefault();

			$( '.wccon-product-list aside' ).addClass( 'opened' );
		}
		closeFilters( e ) {
			e.preventDefault();

			$( '.wccon-product-list aside' ).removeClass( 'opened' );
		}
		toggleExtraHandler( e ) {
			const extraContainer = $( e.currentTarget ).closest( '.wccon-component-extra' );
			const belogsToGroup = $( extraContainer ).parents( '.wccon-group' );
			extraContainer.toggleClass( 'opened' );
			$( '.wccon-component.extra', belogsToGroup ).not( '.selected' ).toggleClass( 'exhide' );
		}
		expandWidget() {
			const bodyWidget = $( this ).closest( '.wccon-widget-body' );
			bodyWidget.find( '.h-widget' ).removeClass( 'h-widget' );
			const scrollContainer = bodyWidget.find( '.wccon-widget-items' );
			scrollContainer.addClass( 'active' );
			$( this ).hide();
			$( this ).closest( '.widget-expander' ).addClass( 'opened' );
			$( this ).siblings( '.widget-expander-hide' ).show();
		}
		collapseWidget() {
			const bodyWidget = $( this ).closest( '.wccon-widget-body' );
			const allOptions = bodyWidget.find( 'li' );

			allOptions.each( ( i, el ) => {
				if ( $( el ).attr( 'data-hide-widget' ) !== undefined ) {
					$( el ).addClass( 'h-widget' );
				}
			} );
			const scrollContainer = bodyWidget.find( '.wccon-widget-items' );
			scrollContainer.removeClass( 'active' );
			$( this ).hide();
			$( this ).closest( '.widget-expander' ).removeClass( 'opened' );
			$( this ).siblings( '.widget-expander-show' ).show();
		}

		maybeStringToNumber( str, float = true ) {
			let numberValue = 0;
			if ( float ) {
				numberValue = parseFloat( str );
			} else {
				numberValue = parseInt( str, 10 );
			}

			numberValue = isNaN( numberValue ) || ! isFinite( numberValue ) ? 0 : numberValue;
			return numberValue;
		}
		maybeSaveToStorage() {
			if ( ! this.saveToStorage ) {
				return;
			}

			//dont save on account page and save-list page
			if ( this.isListExists() ) {
				return;
			}
			const savedItems = localStorage.getItem( 'wccon-list' );
			const parsedSavedItems = savedItems ? JSON.parse( savedItems ) : [];

			const foundExistingList = parsedSavedItems.find(
				( savedItem ) => parseInt( savedItem.id, 10 ) === parseInt( this.builderId, 10 )
			);
			if ( foundExistingList ) {
				const updatedSavedItems = parsedSavedItems.map( ( savedItem ) => {
					if ( parseInt( savedItem.id, 10 ) === parseInt( this.builderId, 10 ) ) {
						const newSavedItem = { ...savedItem, groups: this.productScheme.groups };
						if ( this.currentLang ) {
							newSavedItem.lang = this.currentLang;
						}
						return newSavedItem;
					}
					return savedItem;
				} );
				localStorage.setItem( 'wccon-list', JSON.stringify( updatedSavedItems ) );
				console.log( 'maybeSaveToStorage1', updatedSavedItems, localStorage.getItem( 'wccon-list' ) );
				return;
			}
			const newSavedItem = { groups: this.productScheme.groups, id: this.builderId };
			if ( this.currentLang ) {
				newSavedItem.lang = this.currentLang;
			}
			parsedSavedItems.push( newSavedItem );
			console.log( 'maybeSaveToStorage2', parsedSavedItems, localStorage.getItem( 'wccon-list' ) );
			localStorage.setItem( 'wccon-list', JSON.stringify( parsedSavedItems ) );
		}
		isListExists() {
			const currentUrl = window.location.href;
			const pattern = new RegExp( WCCON_BUILDER_FRONT.endpoint_slug + '\\/\\edit\\/\\d+' );

			if ( pattern.test( currentUrl ) ) {
				return true;
			}

			if ( this.savedListQuery && wccon_parsenumber( this.savedListQuery ) ) {
				return true;
			}
			return false;
		}
		getListId() {
			const currentUrl = window.location.href;
			const pattern = new RegExp( WCCON_BUILDER_FRONT.endpoint_slug + '\\/\\edit\\/(\\d+)' );

			const match = currentUrl.match( pattern );

			if ( match ) {
				return parseInt( match[ 1 ], 10 );
			}

			if ( this.savedListQuery && wccon_parsenumber( this.savedListQuery ) ) {
				return wccon_parsenumber( this.savedListQuery );
			}
			return 0;
		}
		isAccountPage() {
			const currentUrl = window.location.href;
			const pattern = new RegExp( WCCON_BUILDER_FRONT.endpoint_slug + '\\/\\edit\\/\\d+' );

			if ( pattern.test( currentUrl ) ) {
				return true;
			}
			return false;
		}
		/**
		 * Stores the default text for an element so it can be reset later
		 */
		wccon_set_content( el, content ) {
			if ( undefined === el.attr( 'data-o_content' ) ) {
				el.attr( 'data-o_content', el.html() );
			}
			el.html( content );
		}

		/**
		 * Stores the default text for an element so it can be reset later
		 */
		wccon_reset_content( el ) {
			if ( undefined !== el.attr( 'data-o_content' ) ) {
				el.html( el.attr( 'data-o_content' ) );
			}
		}

		wccon_set_variation_attr( el, attr, value ) {
			if ( undefined === el.attr( 'data-o_' + attr ) ) {
				el.attr( 'data-o_' + attr, ! el.attr( attr ) ? '' : el.attr( attr ) );
			}
			if ( false === value ) {
				el.removeAttr( attr );
			} else {
				el.attr( attr, value );
			}
		}

		wccon_reset_variation_attr( el, attr ) {
			if ( undefined !== el.attr( 'data-o_' + attr ) ) {
				el.attr( attr, el.attr( 'data-o_' + attr ) );
			}
		}
	}
	$( document ).ready( function () {
		const builderWrapper = $( '.wccon-builder-wrapper' ).first();
		if ( builderWrapper.length ) {
			new WCCFilterWidget( builderWrapper );
			$( document.body ).addClass( 'wccon-page-builder' );
		}
	} );

	class WCconSavedList {
		constructor() {
			this.successMessage = WCCON_BUILDER_FRONT.i18n_success_message;
			this.copiedMessage = WCCON_BUILDER_FRONT.i18n_copied_message;
			this.successMessageRemove = WCCON_BUILDER_FRONT.i18n_success_message_remove;
			this.errorMessage = WCCON_BUILDER_FRONT.i18n_error_message;
			this.addedToCartMessage = WCCON_BUILDER_FRONT.i18n_added_to_cart;
			this.removeListMessage = WCCON_BUILDER_FRONT.i18n_remove_list;
			this.endpoint = WCCON_BUILDER_FRONT.endpoint;
			this.nonce = WCCON_BUILDER_FRONT.nonce;

			this.removeListButton = $( '.wccon-remove-list' );
			$( document.body )
				.on( 'click', '.wccon-list-button-buy', this.buyProduct )
				.on( 'click', '.wccon-list-button-share', this.toggleShare )
				.on( 'click', '.wccon-close-share', this.toggleShare )
				.on( 'click', '.wccon-saved-list .share-link', this.copyLink.bind( this ) )
				.on( 'wccon_buy_list_success', this.buySuccess.bind( this ) )
				.on( 'wccon_buy_list_error', this.buySuccess.bind( this ) )
				.on( 'click', '.wccon-load-more', this.loadMore );

			this.removeListButton.on( 'click', this.removeList.bind( this ) );

			if ( window.location.href === this.endpoint ) {
				$( document.body ).addClass( 'wccon-page-builder' );
			}
		}
		buyProduct( e ) {
			e.preventDefault();

			const list_id = $( this ).data( 'id' );
			if ( ! list_id ) {
				return;
			}

			$( document.body ).trigger( 'wccon-before-adding-to-cart', [ list_id ] );

			$( this ).attr( 'disabled', true );
			$( this ).addClass( 'loading' );

			$.ajax( {
				url: WCCON_BUILDER_FRONT.ajax_url,
				type: 'POST',
				data: {
					action: 'wccon_buy_list',
					list_id: list_id,
					nonce: WCCON_BUILDER_FRONT.nonce,
				},
			} )
				.then( ( res ) => {
					console.log( res );
					if ( res.success && res.data.fragments ) {
						const $supports_html5_storage = 'sessionStorage' in window && window.sessionStorage !== null;
						if ( typeof window.wc_cart_fragments_params !== 'undefined' ) {
							const cart_hash_key = wc_cart_fragments_params.cart_hash_key;
							$.each( res.data.fragments, function ( key, value ) {
								$( key ).replaceWith( value );
							} );

							if ( $supports_html5_storage ) {
								sessionStorage.setItem(
									wc_cart_fragments_params.fragment_name,
									JSON.stringify( res.data.fragments )
								);

								localStorage.setItem( cart_hash_key, res.data.cart_hash );
								sessionStorage.setItem( cart_hash_key, res.data.cart_hash );

								if ( res.data.cart_hash ) {
									sessionStorage.setItem( 'wc_cart_created', new Date().getTime() );
								}
							}
						}

						$( document.body ).trigger( 'wc_fragments_refreshed' );
						$( document.body ).trigger( 'wccon_buy_list_success', [ res.data ] );
						$( this ).attr( 'disabled', false );
						$( this ).removeClass( 'loading' );
					}
				} )
				.catch( ( err ) => {
					console.log( err );
					$( document.body ).trigger( 'wccon_buy_list_error' );
					$( document.body ).trigger( 'wc_fragments_ajax_error' );
				} );
		}
		buySuccess( e, data ) {
			toastr.success( this.addedToCartMessage );
			setTimeout( () => {
				$( 'html' ).css( 'overflow', 'initial' );
				$( '.wccon-modal' ).fadeOut();
			}, 800 );
		}
		buyError( e, data ) {
			toastr.error( this.errorMessage );
		}
		toggleShare() {
			$( this ).closest( '.wccon-saved-list' ).find( '.saved-lists__share-box' ).toggleClass( 'active' );
		}
		async copyLink( e ) {
			e.preventDefault();
			const currentItem = $( e.currentTarget );
			const link = currentItem.attr( 'href' );

			if ( navigator.clipboard ) {
				try {
					await navigator.clipboard.writeText( link );

					toastr.success( this.copiedMessage );
				} catch ( err ) {
					console.error( 'Failed to copy: ', err );
				}
			} else {
				const textArea = $( '<input />', { type: 'text' } );
				textArea.val( link );
				textArea.css( 'position', 'absolute' );
				textArea.css( 'left', '-9999px' );
				$( document.body ).append( textArea );
				textArea.focus();
				textArea.select();
				document.execCommand( 'copy' );
				textArea.remove();
				toastr.success( this.copiedMessage );
			}
		}
		loadMore( e ) {
			e.preventDefault();

			const currentPage = $( this ).attr( 'data-current' );
			const totalPages = $( this ).attr( 'data-total' );

			//not yet supported several builders on same page.
			const shortcode_id = $( '.wccon-builder-wrapper' ).first().data( 'wccon-builder' );

			if ( wccon_parsenumber( totalPages ) <= wccon_parsenumber( currentPage ) ) {
				return;
			}
			const modalContainer = $( this ).closest( '.wccon-modal' );
			const listType = $( this ).data( 'type' );

			$( this ).addClass( 'loading' );
			$( this ).attr( 'disabled', true );
			if ( listType === 'user' ) {
				wccon_generate_skeleton_list( '#wccon-user-list .wccon-saved-lists', 6, true, true );
				wccon_resize_window_el( modalContainer );
			} else {
				wccon_generate_skeleton_list( '#wccon-users-list .wccon-saved-lists', 6, true, true );
				wccon_resize_window_el( modalContainer );
			}

			$.ajax( {
				url: WCCON_BUILDER_FRONT.ajax_url,
				type: 'POST',
				data: {
					action: 'wccon_load_more',
					nonce: WCCON_BUILDER_FRONT.nonce,
					listType,
					currentPage,
					shortcode_id: shortcode_id,
				},
			} )
				.then( ( res ) => {
					console.log( res );
					if ( res.success ) {
						modalContainer.find( '.wccon-skeleton-item' ).remove();
						const html = $.parseHTML( res.data.html );

						let emptyArticles = true;
						$( html[ 0 ] )
							.find( '.wccon-saved-list' )
							.each( function ( i, el ) {
								emptyArticles = false;
								$( '.wccon-saved-lists', modalContainer ).append( $( el ) );
							} );
						wccon_resize_window_el( modalContainer );
						$( this ).removeClass( 'loading' );
						$( this ).attr( 'disabled', false );
						$( this ).attr( 'data-current', wccon_parsenumber( currentPage ) + 1 );
						if ( wccon_parsenumber( totalPages ) <= wccon_parsenumber( currentPage ) + 1 ) {
							$( this ).hide();
						}
					}
				} )
				.catch( ( err ) => {
					console.log( err );
				} );
		}
		removeList( e ) {
			e.preventDefault();
			const listId = $( e.currentTarget ).closest( 'button' ).data( 'id' );
			const callback = wp.hooks.applyFilters(
				'wccon-remove-list',
				() => {
					if ( window.confirm( this.removeListMessage ) ) {
						$.ajax( {
							url: WCCON_BUILDER_FRONT.ajax_url,
							type: 'POST',
							data: { action: 'wccon_remove_list', listId, nonce: WCCON_BUILDER_FRONT.nonce },
						} )
							.then( ( res ) => {
								console.log( res );
								if ( res.success && res.data.deleted ) {
									toastr.success( this.successMessageRemove );
									$( `.wccon-saved-list[data-id="${ res.data.list_id }"]` ).remove();
								} else {
									toastr.error( this.errorMessage );
								}
							} )
							.catch( ( err ) => {
								console.log( err );
							} );
					}
				},
				listId,
				this
			);
			callback();
		}
	}
	new WCconSavedList();

	$( window ).resize( function () {
		wccon_resize_window();
	} );
} )( jQuery );
