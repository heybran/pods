class PodsTooltip extends HTMLElement {
	constructor() {
		super();
		this.attachShadow( { mode: 'open' } );
		this.defer = ( window.requestIdleCallback || requestAnimationFrame ).bind( window );
		window.addEventListener( 'scroll', this.setPositionOnScroll.bind( this ) );
	}

	connectedCallback() {
		this.ariaExpanded = 'false';
		this.shadowRoot.innerHTML = `
      <style>${ this.constructor.css }</style>
      <div part="wrapper" class="tooltip-wrapper">
        <button
          part="button"
          class="tooltip-button"
          onfocusin="this.getRootNode().host.openTooltip()"
          >
          <svg xmlns="http://www.w3.org/2000/svg" role="presentation" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-help-circle"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          <span class="sr-only"></span>
        </button>
        <div slot="content" class="tooltip-content">
          ${ this.innerHTML }
        </div>
      </div>
    `;
		this.tooltipContent.addEventListener( 'transitionend', () => {
			if ( this.tooltipContent.classList.contains( 'visible' ) ) {
				this.ariaExpanded = 'true';
			} else {
				this.ariaExpanded = 'false';
			}
		} );
		this.defer( () => {
			this.innerHTML = '<!-- Original content has been moved into shadowRoot -->';
		} );
	}

	get button() {
		return this.shadowRoot.querySelector( 'button' );
	}

	get buttonWidth() {
		return this.button.getBoundingClientRect().width;
	}

	get tooltipContent() {
		return this.shadowRoot.querySelector( '.tooltip-content' );
	}

	get tooltipContentHeight() {
		return this.tooltipContent.getBoundingClientRect().height;
	}

	static closeTooltip() {
		this.tooltipContent.classList.remove( 'visible' );
		this.removeEventListener( 'focusout', PodsTooltip.closeTooltip );
	}

	openTooltip() {
		this.setPosition();
		this.tooltipContent.classList.add( 'visible' );
		this.addEventListener( 'focusout', PodsTooltip.closeTooltip );
	}

	setPosition() {
		const { left, top } = this.button.getBoundingClientRect();
		const y = top - ( this.tooltipContentHeight - 2 );
		const x = left + ( this.buttonWidth / 2 );
		this.style.setProperty( '--tooltip-left', `${ x }px` );
		this.style.setProperty( '--tooltip-top', `${ y }px` );
	}

	setPositionOnScroll() {
		if ( this.ariaExpanded === 'false' ) {
			return;
		}
		this.setPosition();
	}

	static get css() {
		return `
    :host {
      display: inline-flex;
      --tooltip-arrow-size: 12px;
    }

    svg {
      width: 18px;
      height: 18px;
    }

    button {
      background: transparent;
      border: none;
      border-radius: 50%;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: inherit;
    }

    button:is(:hover, :focus-visible, :focus) {
      outline-offset: 2px;
      outline: 1px auto #007cba;
    }

    .tooltip-wrapper {
      position: relative;
      display: inline-flex;
    }

    .tooltip-content {
      position: fixed;
      opacity: 0;
      pointer-events: none;
      background-color: var(--tooltip-background-color, #333);
      border-radius: 4px;
      padding: 1em 1.2em;
      left: var(--tooltip-left);
      top: calc(var(--tooltip-top) - var(--tooltip-arrow-size));
      transform: translateX(-50%);
      transition: opacity .3s ease;
      will-change: opacity;
      width: max(25em, 300px);
      z-index: 99999;
      display: grid;
      gap: .75em;
      color: #fff;
    }

    .tooltip-content * {
      color: inherit;
      margin: 0;
    }

    .tooltip-content::before {
      content: '';
      position: absolute;
      bottom: calc(var(--tooltip-arrow-size) * -0.5);
      left: 0;
      border-width: 8px 8px 0;
      border-top-color: initial;
      width: var(--tooltip-arrow-size);
      height: var(--tooltip-arrow-size);
      background: var(--tooltip-background-color, #333);
      transform: rotate(45deg);
      left: calc(50% - var(--tooltip-arrow-size) / 2);
    }

    .tooltip-content.visible {
      opacity: 1;
      pointer-events: initial;
    }
    `;
	}
}

customElements.define( 'pods-tooltip', PodsTooltip );
