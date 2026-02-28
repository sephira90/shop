interface ResolvePageAfterLastItemRemovalOptions {
    currentPage: number;
    visibleItemsCount: number;
}

export const resolvePageAfterLastItemRemoval = ({
    currentPage,
    visibleItemsCount,
}: ResolvePageAfterLastItemRemovalOptions): number => {
    return visibleItemsCount === 1 && currentPage > 1 ? currentPage - 1 : currentPage;
};
