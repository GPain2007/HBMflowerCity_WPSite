(function ($) {
    'use strict';

    // Clear localStorage values on page refresh so that category selection doesn't persist through refresh
    $(window).on('load', function () {
        localStorage.removeItem('sb-category-preference');
    });

    var ElementorSectionsButton = {
        modal: null,
        currentTab: 'pages',
        currentCategory: '',
        scrollPosition: 0,
        loadingElement: $('<div class="sb-loading-area"><svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="Loader"><g id="Icon"><g clip-path="url(#clip0_826_641)"><rect width="400" height="400" rx="200" fill="black"></rect><circle id="Ellipse 1" cx="283.333" cy="183.333" r="33.3333" fill="#E1E4EB"></circle><path id="Vector 1" d="M86.5739 164.909L-33.3335 334.818V433.333H400V301.602C387.037 289.272 358.426 261.813 347.685 250.625C336.944 239.437 321.913 245.963 315.741 250.625L260.648 301.602C219.136 261.001 132.963 176.819 120.37 164.909C107.778 152.999 92.5925 159.947 86.5739 164.909Z" fill="#E1E4EB"></path></g><rect x="2.5" y="2.5" width="395" height="395" rx="197.5" stroke="#F1F2F4" stroke-width="5"></rect></g><path id="Ellipse 17" d="M400 200C400 310.457 310.457 400 200 400C89.5431 400 0 310.457 0 200C0 89.5431 89.5431 0 200 0C310.457 0 400 89.5431 400 200ZM24.9485 200C24.9485 296.678 103.322 375.051 200 375.051C296.678 375.051 375.051 296.678 375.051 200C375.051 103.322 296.678 24.9485 200 24.9485C103.322 24.9485 24.9485 103.322 24.9485 200Z" fill="url(#paint0_linear_826_641)"></path></g><defs><linearGradient id="paint0_linear_826_641" x1="200" y1="9.23872e-06" x2="200" y2="400" gradientUnits="userSpaceOnUse"><stop stop-color="#929292"></stop><stop offset="0.615223" stop-color="#CDCDCD"></stop><stop offset="1" stop-color="#EAEAEA"></stop></linearGradient><clipPath id="clip0_826_641"><rect width="400" height="400" rx="200" fill="white"></rect></clipPath></defs></svg></div>'),
        nextElementId: null,

        init: function () {

            elementor.on('preview:loaded', this.onPreviewLoaded.bind(this));
        },

        onPreviewLoaded: function () {
            // console.log('Preview loaded, adding button');
            this.addButton();
            // Add these lines here instead
            this.modal = $('.sb-library-modal');
            this.initModalEvents();
            this.attachGlobalListener();
        },

        addButton: function () {

            // console.log('ElementorSectionsButton.addButton() called');
            let add_section_tmpl = $('#tmpl-elementor-add-section');

            if (add_section_tmpl.length > 0) {
                // console.log('Template found, modifying...');
                let action_for_add_section = add_section_tmpl.text();

                action_for_add_section = action_for_add_section.replace(
                    '<div class="elementor-add-section-drag-title',
                    '<div class="elementor-add-section-area-button elementor-add-sb-button" title="Super Blank">' +
                    '<span class="sb-icon" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; padding-top: 1px;">' +
                    '<svg width="30" height="30" viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_599_1017)"><rect width="128" height="128" rx="64" fill="black"/><circle cx="90.6667" cy="58.6667" r="10.6667" fill="#E1E4EB"/><path d="M27.7034 52.7708L-10.667 107.142V138.667H128V96.5126C123.852 92.5668 114.696 83.7799 111.259 80.1997C107.822 76.6196 103.012 78.708 101.037 80.1997L83.4071 96.5126C70.1232 83.52 42.5478 56.582 38.5182 52.7708C34.4886 48.9597 29.6293 51.1829 27.7034 52.7708Z" fill="#E1E4EB"/></g><rect x="2" y="2" width="124" height="124" rx="62" stroke="black" stroke-width="4"/><defs><clipPath id="clip0_599_1017"><rect width="128" height="128" rx="64" fill="white"/></clipPath></defs></svg>' +
                    '</span>' +
                    '</div>' +
                    '<div class="elementor-add-section-drag-title'
                );

                add_section_tmpl.text(action_for_add_section);
            } else {
                console.log('Template not found');
            }
        },

        initModalEvents: function () {
            // Close button
            this.modal.find('.sb-library-modal-close').on('click', this.closeModal.bind(this));

            // Close on overlay click
            this.modal.find('.sb-library-modal-overlay').on('click', this.closeModal.bind(this));

            // Tab switching
            this.modal.find('.sb-library-tab').on('click', this.switchTab.bind(this));

            // Category selection
            this.modal.find('.sb-library-category-select').on('change', this.filterTemplates.bind(this));

            // Prevent modal content clicks from bubbling to overlay
            this.modal.find('.sb-library-modal-container').on('click', function (e) {
                e.stopPropagation();
            });

            // Add this new event binding - todo remove
            this.modal.find('.sb-library-reload-styles').on('click', this.reloadStyles.bind(this));

            // Theme toggle
            this.modal.find('.sb-library-theme-toggle').on('click', this.toggleTheme.bind(this));
        },

        openModal: function () {

            this.modal.show();

            // Restore theme and category preferences
            const savedTheme = localStorage.getItem('sb-theme-preference');
            const savedCategory = localStorage.getItem('sb-category-preference');

            if (savedTheme === 'dark') {
                this.modal.addClass('sb-dark-theme');
            }

            this.loadTemplates().then(() => {
                if (savedCategory) {
                    this.modal.find('.sb-library-category-select').val(savedCategory);
                    this.filterTemplates({ target: { value: savedCategory } });
                }
            });
        },

        closeModal: function () {
            // Save current state
            const session = {
                currentTab: this.currentTab,
                currentCategory: this.modal.find('.sb-library-category-select').val(),
                scrollPosition: this.modal.find('.sb-library-content-wrapper').scrollTop()
            };

            localStorage.setItem('sb-library-session', JSON.stringify(session));
            this.modal.hide();
        },

        switchTab: function (event) {
            const $tab = $(event.currentTarget);
            const tab = $tab.data('tab');

            // Update active tab
            this.modal.find('.sb-library-tab').removeClass('active');
            $tab.addClass('active');

            this.currentTab = tab;
            this.loadTemplates();

            // Reset category selection when switching tabs
            this.currentCategory = '';
            this.scrollPosition = 0;
        },

        loadTemplates: function () {
            return new Promise((resolve) => {
                const grid = this.modal.find('.sb-library-templates-grid');
                grid.empty();

                // Remove modal type class
                grid.parent().removeClass(function (index, className) {
                    return (className.match(/(^|\s)sb-modal-type-\S+/g) || []).join(' ');
                });

                // Add modal type class
                grid.parent().addClass('sb-modal-type-' + this.currentTab);

                // Filter templates based on current tab and category
                const templates = this.getTemplatesForCurrentTab();
                const categories = this.getCategoriesForCurrentTab();

                // Update category dropdown
                const categorySelect = this.modal.find('.sb-library-category-select');
                categorySelect.empty().append('<option value="">All Categories</option>');
                categories.forEach(category => {

                    categorySelect.append(`<option value="${category}">${category.replace(/-/g, ' ')}</option>`);
                });

                // Add templates to grid
                templates.forEach(template => {
                    const templateItem = this.createTemplateItem(template);
                    grid.append(templateItem);
                });

                resolve();
            });
        },

        getTemplatesForCurrentTab: function () {

            return elementorSectionsData.templates[this.currentTab] || [];
        },

        getCategoriesForCurrentTab: function () {
            const templates = this.getTemplatesForCurrentTab();
            const categories = new Set();
            const categoryMap = new Map();

            templates.forEach(template => {
                if (template.category && !categories.has(template.category)) {
                    categories.add(template.category);
                    categoryMap.set(template.category, template.category_order);
                }
            });

            return Array.from(categories)
                .sort((a, b) => {
                    const orderA = categoryMap.get(a);
                    const orderB = categoryMap.get(b);
                    return orderA.localeCompare(orderB);
                });
        },

        createTemplateItem: function (template) {
            const itemClass = template.type === 'Sections' ? 'sb-library-template-item sb-section-template' : 'sb-library-template-item';
            const item = $(`<div class="${itemClass}"></div>`);
            item.append(`
                <div class="sb-library-template-preview">
                    <img src="${template.thumbnail || 'path/to/default/thumbnail.jpg'}" alt="${template.name}">
                </div>
            `);

            item.on('click', () => this.importTemplate(template));
            return item;
        },

        toggleLoading: function (add) {

            const loadingElement = $('.sb-library-content-wrapper').find(this.loadingElement);

            if (loadingElement.length > 0) {

                loadingElement.remove();
            }

            if (add) {

                $('.sb-library-content-wrapper').append(this.loadingElement);
            }
        },
        
        importTemplate: function (template) {

            this.toggleLoading(true);

            this.loadAndImportTemplate(template.file);            
        },

        loadAndImportTemplate: function (templateFile) {

            const _this = this;

            $.ajax({
                url: elementorSectionsData.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_elementor_template',
                    nonce: elementorSectionsData.nonce,
                    template: templateFile
                },
                success: function (response) {

                    if (response.success) {
                        
                        const templateData = response.data;

                        function regenerateIds(element) {
                            if (element.id) {
                                element.id = elementorCommon.helpers.getUniqueId();
                            }
                            if (element.elements && element.elements.length) {
                                element.elements = element.elements.map(regenerateIds);
                            }
                            return element;
                        }

                        templateData.content.forEach((element) => {

                            let at = elementor.getPreviewContainer().children.length;

                            if(!!_this.nextElementId) {

                                const atIndex = _this.getAtPosition(elementor.getPreviewContainer().children, _this.nextElementId);

                                if(atIndex !== null) {

                                    at = atIndex;
                                }
                            }

                            const modelWithNewIds = regenerateIds(element);

                            $e.run('document/elements/create', {
                                model: modelWithNewIds,
                                container: elementor.getPreviewContainer(),
                                options: {
                                    at: at
                                }
                            });
                        });
                    } else {

                        console.error('Failed to load template:', response.data);
                        
                    }

                    _this.closeInserterBox();
                    _this.toggleLoading(false);
                    _this.closeModal();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Failed to load template:', textStatus, errorThrown);
                }
            });
        },

        // close inserter box
        closeInserterBox: function () {

            const elementorFrame = document.querySelector('#elementor-preview-iframe');

            if (elementorFrame && elementorFrame.contentDocument) {
                
                const closeButton = elementorFrame.contentDocument.querySelector('.elementor-add-section-close');
                
                if (closeButton) {
                    
                    closeButton.click();
                }
            }
        },

        getAtPosition: function (children, elementId) {

            if(children.length === 0) return null;

            let atIndex = 0;

            children.forEach(function(value, index) {

                if(value.id === elementId) {

                    atIndex = index;
                }
            });

            return atIndex;
        },

        onButtonClick: function (event) {
            // console.log('onButtonClick function called');
            event.preventDefault();
            event.stopPropagation();
            // console.log('Super Blank button clicked');

            // Prevent Elementor from deselecting the current element
            event.stopImmediatePropagation();

            this.prepareAtIndex(event.target);

            this.openModal();
        },

        prepareAtIndex: function (button) {

            this.nextElementId = null;

            if(button.length===0) return;

            const parentElement = $(button).closest('.elementor-add-section');

            if(parentElement.length===0) return;

            const nextElement = parentElement.next('.elementor-element');

            if(nextElement.length===0) return;
            
            this.nextElementId = $(nextElement).attr('data-id')
        },

        attachGlobalListener: function () {
            elementor.$previewContents.off('click', '.elementor-add-sb-button').on('click', '.elementor-add-sb-button', this.onButtonClick.bind(this));
            // console.log('Global click listener attached to preview contents');
        },

        filterTemplates: function (event) {
            const selectedCategory = event.target.value;

            localStorage.setItem('sb-category-preference', selectedCategory);

            const templates = this.getTemplatesForCurrentTab();
            const grid = this.modal.find('.sb-library-templates-grid');

            grid.empty();

            const filteredTemplates = selectedCategory
                ? templates.filter(template => template.category === selectedCategory)
                : templates;

            filteredTemplates.forEach(template => {
                const templateItem = this.createTemplateItem(template);
                grid.append(templateItem);
            });
        },

        // todo remove
        reloadStyles: function () {
            const linkElement = $('link[href*="sb-library.css"]');
            const href = linkElement.attr('href');

            // Add timestamp to force browser to reload the CSS
            const newHref = href.split('?')[0] + '?v=' + new Date().getTime();

            // Create new link element
            const newLink = $('<link>', {
                rel: 'stylesheet',
                type: 'text/css',
                href: newHref
            });

            // Insert new link before old one
            linkElement.after(newLink);

            // Remove old link after new one is loaded
            newLink.on('load', function () {
                linkElement.remove();
            });
        },

        toggleTheme: function () {
            this.modal.toggleClass('sb-dark-theme');
            // Optionally save the preference
            localStorage.setItem('sb-theme-preference', this.modal.hasClass('sb-dark-theme') ? 'dark' : 'light');
        }
    };

    $(window).on('elementor:init', function () {
        // console.log('elementor:init event fired');
        ElementorSectionsButton.init();
    });

    // console.log('Elementor Sections script loaded');

})(jQuery);
