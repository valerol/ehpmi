// Read-only excerpt: installed WordPress 7.1 wp-includes/js/dist/edit-widgets.js
// Lines 1979–1990 and 2009–2025 establish the first-root ownership boundary.
function transformWidgetToBlock(widget) {
    if (widget.id_base === "block") {
        const parsedBlocks = (0, import_blocks.parse)(widget.instance.raw.content, {
            __unstableSkipAutop: true
        });
        if (!parsedBlocks.length) {
            return (0, import_widgets.addWidgetIdToBlock)(
                (0, import_blocks.createBlock)("core/paragraph", {}, []),
                widget.id
            );
        }
        return (0, import_widgets.addWidgetIdToBlock)(parsedBlocks[0], widget.id);
    }
}
// The corresponding non-legacy branch of transformBlockToWidget:
function serializeOneWidgetBlock(block, relatedWidget = {}) {
    return {
        ...relatedWidget,
        id_base: "block",
        instance: {
            raw: {
                content: (0, import_blocks.serialize)(block)
            }
        }
    };
}
