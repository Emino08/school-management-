export const drawerWidth = 240;

export const StyledTableCell = ({ head, children, ...props }) => {
    const className = head
        ? "bg-black text-white px-4 py-3 text-sm font-semibold"
        : "px-4 py-3 text-sm";
    return (
        <td className={className} {...props}>
            {children}
        </td>
    );
};

export const StyledTableRow = ({ children, ...props }) => {
    return (
        <tr className="odd:bg-gray-50 hover:bg-gray-100 transition-colors" {...props}>
            {children}
        </tr>
    );
};

// Placeholder components for backward compatibility
// These should be replaced with proper layouts in Dashboard files
export const AppBar = ({ children, className, open, ...props }) => {
    return (
        <header
            className={`fixed top-0 right-0 z-10 transition-all duration-300 ${
                open ? `left-[${drawerWidth}px]` : 'left-0'
            } ${className || ''}`}
            {...props}
        >
            {children}
        </header>
    );
};

export const Drawer = ({ children, className, open, ...props }) => {
    return (
        <aside
            className={`fixed left-0 top-0 h-full bg-white border-r border-gray-200 transition-all duration-300 overflow-hidden ${
                open ? `w-[${drawerWidth}px]` : 'w-0'
            } ${className || ''}`}
            {...props}
        >
            <div className="p-4">
                {children}
            </div>
        </aside>
    );
};