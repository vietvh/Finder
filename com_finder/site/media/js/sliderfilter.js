var FinderFilter = Fx.Elements.extend({
	options: {
		onActive: Class.empty,
		onBackground: Class.empty,
		height: false,
		width: true,
		opacity: true,
		fixedHeight: false,
		fixedWidth: 220,
		wait: true,
	},
	initialize: function (togglers, elements, container, frame) {
		this.togglers = togglers || [];
		this.elements = elements || [];
		this.container = $(container);
		this.frame = $(frame);
		this.effects = {};
		if (this.options.opacity) this.effects.opacity = 'fullOpacity';
		if (this.options.width) this.effects.width = this.options.fixedWidth ? 'fullWidth' : 'offsetWidth';
		this.container.setStyle('width', '230px');
		this.addEvent('onActive', function (toggler, element) {
			element.setStyle('border-top', '1px solid #ccc');
			element.setStyle('border-right', '1px solid #ccc');
			element.setStyle('border-bottom', '1px solid #ccc');
			element.setStyle('overflow', 'auto');
			$(this.container).setStyle('width', $(this.container).getStyle('width').toInt() + element.fullWidth);
			coord = element.getCoordinates([this.frame]);
			scroller = new Fx.Scroll(this.frame);
			scroller.scrollTo(coord.top, coord.left);
		});
		this.addEvent('onBackground', function () {
			el = this.elements[this.active];
			el.getElements('input').each(function (n) {
				n.removeProperty('checked');
			});
		});
		this.addEvent('onComplete', function () {
			el = this.elements[this.active];
			if (!el.getStyle('width').toInt()) {
				$(this.container).setStyle('width', $(this.container).getStyle('width').toInt() - el.fullWidth);
			}
			this.active = null;
		});
		for (var i = 0, l = this.togglers.length; i < l; i++) this.addSection(this.togglers[i], this.elements[i]);
		this.elements.each(function (el, i) {
			var cbs = el.getElements('input.selector').length;
			var cba = 0;
			el.getElements('input.selector').each(function (n) {
				if (n.getProperty('checked')) {
					this.togglers[i].setProperty('checked', 'checked');
					cba += 1;
				}
			}, this);
			if (cbs > 0 && cbs === cba && $chk(el.getElement('input.branch-selector'))) {
				el.getElement('input.branch-selector').setProperty('checked', 'checked');
			}
			if (cba) {
				this.fireEvent('onActive', [this.togglers[i], el]);
			} else {
				for (var fx in this.effects) el.setStyle(fx, 0);
			}
			el.getElement('dt').getElement('input').addEvent('change', function (e) {
				if (e.target.getProperty('checked')) {
					el.getElements('dd').each(function (dd) {
						dd.getElement('input').setProperty('checked', 'checked');
					});
				} else {
					el.getElements('dd').each(function (dd) {
						dd.getElement('input').removeProperty('checked');
					});
				}
			});
		}, this);
	},
	addSection: function (toggler, element, pos) {
		toggler = $(toggler);
		element = $(element);
		var test = this.togglers.contains(toggler);
		var len = this.togglers.length;
		this.togglers.include(toggler);
		this.elements.include(element);
		if (len && (!test || pos)) {
			pos = $pick(pos, len - 1);
			toggler.injectBefore(this.togglers[pos]);
			element.injectAfter(toggler);
		} else if (this.container && !test) {
			toggler.inject(this.container);
			element.inject(this.container);
		}
		var idx = this.togglers.indexOf(toggler);
		toggler.addEvent('click', this.toggle.bind(this, idx));
		if (this.options.width) element.setStyles({
			'padding-left': 0,
			'border-left': 'none',
			'padding-right': 0,
			'border-right': 'none'
		});
		element.fullOpacity = 1;
		if (this.options.fixedWidth) element.fullWidth = this.options.fixedWidth;
		if (this.options.fixedHeight) element.fullHeight = this.options.fixedHeight;
		element.setStyle('overflow', 'hidden');
		return this;
	},
	toggle: function (index) {
		index = ($type(index) == 'element') ? this.elements.indexOf(index) : index;
		if (this.timer && this.options.wait) return this;
		this.active = index;
		var obj = {};
		obj[index] = {};
		var el = this.elements[index];
		if (this.togglers[index].getProperty('checked')) {
			for (var fx in this.effects) obj[index][fx] = el[this.effects[fx]];
			this.start(obj);
			this.fireEvent('onActive', [this.togglers[index], el]);
		} else {
			for (var fx in this.effects) obj[index][fx] = 0;
			this.start(obj);
			this.fireEvent('onBackground', [this.togglers[index], el]);
		}
		return this;
	},
});
window.addEvent('domready', function () {
	Filter = new FinderFilter($ES('input.toggler'), $ES('dl.checklist'), $('finder-filter-container'), $('finder-filter-window'));
	$('tax-select-all').addEvent('change', function () {
		if ($('tax-select-all').getProperty('checked')) {
			$('finder-filter-window').getElements('input').each(function (input) {
				if (input.getProperty('id') != 'tax-select-all') {
					input.removeProperty('checked');
				}
			});
		}
	});
});