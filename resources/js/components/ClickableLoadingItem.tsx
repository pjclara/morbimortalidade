function ClickableLoadingItem({
    id,
    isPrincipal,
    isLoading,
    onClick,
    children,
}) {
    return (
        <li
            onClick={() => !isLoading && onClick(id)}
            className={
                isPrincipal
                    ? 'cursor-pointer font-bold text-green-600'
                    : 'cursor-pointer'
            }
        >
            <div className="flex items-center gap-2">
                {children}

                {isPrincipal && (
                    <span className="rounded bg-green-100 px-2 py-1 text-xs text-green-700">
                        Principal
                    </span>
                )}

                {isLoading && (
                    <span className="text-xs text-gray-500 animate-pulse">
                        a atualizar...
                    </span>
                )}
            </div>
        </li>
    );
}

export default ClickableLoadingItem;