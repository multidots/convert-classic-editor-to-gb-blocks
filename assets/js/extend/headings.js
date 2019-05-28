import assign from 'lodash.assign';
const { __ } = wp.i18n;
const { Fragment } = wp.element;
const { addFilter } = wp.hooks;
const { createHigherOrderComponent } = wp.compose;
const { InspectorControls } = wp.editor;
const { PanelBody, TextControl,TextareaControl } = wp.components;
 export const extendHeadings = () => {
   
// Enable spacing control on the following blocks
    const enableCustomControlOnBlocks = [
        'core/heading',
    ];



    // Available spacing control options
    const customControlOptions = [

        {
            label: __('Custom Attributes'),
            value: '',
        },
    ];



    const addCustomControlAttribute = (settings, name) => {
        // Do nothing if it's another block than our defined ones.
        if (!enableCustomControlOnBlocks.includes(name)) {
            return settings;
        }

        // Use Lodash's assign to gracefully handle if attributes are undefined
        settings.attributes = assign(settings.attributes, {
            
            custom_attributes: {
                type: 'string',
                default: '',
            },

        });

        return settings;
    };

    addFilter('blocks.registerBlockType', 'extend-block-example/attribute/spacing', addCustomControlAttribute);


    /**
     * Create HOC to add spacing control to inspector controls of block.
     */
    const withSpacingControl = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            // Do nothing if it's another block than our defined ones.
            if (!enableCustomControlOnBlocks.includes(props.name)) {
                return (
                    <BlockEdit {...props} />
                );
            }

            const { style, id, custom_attributes } = props.attributes;

            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <InspectorControls>
                       
                       <PanelBody
                            title={__('Custom Attributes')}
                            initialOpen={true}
                        >
                            <TextareaControl
                                label={__('You can add multiple Custom Attributes here. Example: title=Hello,data-attr=one')}
                                value={custom_attributes}
                                onChange={(selectedSpacingOption) => {
                                    props.setAttributes({
                                        custom_attributes: selectedSpacingOption,
                                    });
                                }}
                            />
                        </PanelBody>

                    </InspectorControls>
                </Fragment>
            );
        };
    }, 'withSpacingControl');

    addFilter('editor.BlockEdit', 'extend-block-example/with-spacing-control', withSpacingControl);

    const addCustomExtraProps = (saveElementProps, blockType, attributes) => {
        // Do nothing if it's another block than our defined ones.
        if (!enableCustomControlOnBlocks.includes(blockType.name)) {
            return saveElementProps;
        }

        if (attributes.custom_attributes != '') {

            // Use Lodash's assign to gracefully handle if attributes are undefined

            var custom_attr = attributes.custom_attributes.split(',');
            custom_attr.forEach(function (attrData) {
                var split_err = attrData.split('=');
                var first_attr = split_err[0];
                var second_attr = split_err[1];
                var custom_attr_string = first_attr + '="' + second_attr + '"';
            });

            assign(saveElementProps, { custom_attributes: attributes.custom_attributes });
            
        }



        return saveElementProps;
    };

    addFilter('blocks.getSaveContent.extraProps', 'extend-block-example/get-save-content/extra-props', addCustomExtraProps);

}