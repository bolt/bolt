/**
 * Startup
 */
$(function() {
    $('editable').each(function() {
        $element = $(this);
        options = $element.data('options') || {};

        $element.raptor({
            plugins: {
                save: {
                    plugin: 'saveJson'
                },
                saveJson: {
                    url: '/edit/saveit',
                    // type: 'get',
                    // The parameter name for the posted data
                    postName: 'editcontent',
                    // A string or function that returns the identifier for the Raptor instance being saved
                    id: function() {
                        return this.raptor.getElement().data('content_id'); // slug
                    },
                    parameters: function() {
                        return this.raptor.getElement().data('parameters'); // other data
                    }
                },
                dockToScreen: options.dockToScreen,
                dockToElement: options.dockToElement,
                guides: options.guides,
                viewSource: options.viewSource,
                historyUndo: options.historyUndo,
                historyRedo: options.historyRedo,
                alignLeft: options.alignLeft,
                alignCenter: options.alignCenter,
                alignJustify: options.alignJustify,
                alignRight: options.alignRight,
                textBold: options.textBold,
                textItalic: options.textItalic,
                textUnderline: options.textUnderline,
                textStrike: options.textStrike,
                textSuper: options.textSuper,
                textSub: options.textSub,
                listUnordered: options.listUnordered,
                listOrdered: options.listOrdered,
                hrCreate: options.hrCreate,
                textBlockQuote: options.textBlockQuote,
                clearFormatting: options.clearFormatting,
                linkCreate: options.linkCreate,
                linkRemove: options.linkRemove,
                floatLeft: options.floatLeft,
                floatNone: options.floatNone,
                floatRight: options.floatRight,

                languageMenu: options.languageMenu === true,
                statistics: options.statistics === true,
                logo: options.logo === true,
                textSizeDecrease: options.textSizeDecrease === true,
                textSizeIncrease: options.textSizeIncrease === true,
                fontFamilyMenu: options.fontFamilyMenu === true,
                embed: options.embed === true,
                insertFile: options.insertFile === true,
                colorMenuBasic: options.colorMenuBasic === true,
                tagMenu: options.tagMenu === true,
                classMenu: options.classMenu === true,
                snippetMenu: options.snippetMenu === true,
                specialCharacters: options.specialCharacters === true,
                tableCreate: options.tableCreate === true,
                tableInsertRow: options.tableInsertRow === true,
                tableDeleteRow: options.tableDeleteRow === true,
                tableInsertColumn: options.tableInsertColumn === true,
                tableDeleteColumn: options.tableDeleteColumn === true
            }
        })
    });
});
