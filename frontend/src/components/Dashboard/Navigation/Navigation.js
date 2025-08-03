import React from 'react';
import './Navigation.css';

/**
 * Navigation Component
 * 
 * Renders a sidebar navigation menu with grouped menu items.
 * Supports different categories and handles active state management.
 * 
 * @param {Object} props
 * @param {Array} props.menuItems - Array of menu item objects
 * @param {string} props.activeComponent - Currently active component ID
 * @param {Function} props.onMenuItemClick - Callback when menu item is clicked
 * @param {string} props.className - Additional CSS classes
 */
const Navigation = ({
    menuItems = [],
    activeComponent,
    onMenuItemClick,
    className = ""
}) => {

    // Group menu items by category
    const groupedMenuItems = menuItems.reduce((groups, item) => {
        const category = item.category || 'general';
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(item);
        return groups;
    }, {});

    /**
     * Handle menu item click
     * @param {string} itemId - ID of the clicked menu item
     */
    const handleMenuItemClick = (itemId) => {
        if (onMenuItemClick) {
            onMenuItemClick(itemId);
        }
    };

    /**
     * Get category display name
     * @param {string} category - Category key
     * @return {string} Display name for the category
     */
    const getCategoryDisplayName = (category) => {
        const categoryNames = {
            'dev': 'Development Tools',
            'app': 'Application',
            'general': 'General'
        };
        return categoryNames[category] || category.charAt(0).toUpperCase() + category.slice(1);
    };

    /**
     * Render a single menu item
     * @param {Object} item - Menu item object
     * @return {JSX.Element} Menu item button
     */
    const renderMenuItem = (item) => (
        <button
            key={item.id}
            className={`btn text-start p-3 border-0 rounded-3 ${activeComponent === item.id
                ? 'btn-light text-primary shadow-sm'
                : 'btn-outline-light text-white'
                }`}
            onClick={() => handleMenuItemClick(item.id)}
            title={item.description}
        >
            <div className="d-flex align-items-center">
                <span className="fs-5 me-3" role="img" aria-label={item.name}>
                    {item.icon}
                </span>
                <div>
                    <div className="fw-semibold">{item.name}</div>
                    <small className={activeComponent === item.id ? 'text-muted' : 'text-white-75'}>
                        {item.description}
                    </small>
                </div>
            </div>
        </button>
    );

    /**
     * Render a category section
     * @param {string} category - Category key
     * @param {Array} items - Menu items for this category
     * @return {JSX.Element} Category section
     */
    const renderCategorySection = (category, items) => {
        if (items.length === 0) return null;

        return (
            <div key={category} className="mb-4">
                <h6 className="text-uppercase text-white-50 fw-bold mb-3"
                    style={{ fontSize: '0.75rem', letterSpacing: '1px' }}>
                    {getCategoryDisplayName(category)}
                </h6>
                <div className="d-grid gap-2">
                    {items.map(renderMenuItem)}
                </div>
            </div>
        );
    };

    return (
        <div className={`col-md-3 col-lg-2 ${className}`}>
            <div className="bg-primary text-white p-0 min-vh-100 shadow-sm">
                {/* Navigation Header */}
                <div className="p-4 border-bottom border-white border-opacity-25">
                    <h4 className="mb-2 fw-bold">
                        <span className="me-2" role="img" aria-label="Dashboard">🛠️</span>
                        Dashboard
                    </h4>
                    <small className="text-white-50">Development & App Tools</small>
                </div>

                {/* Navigation Menu */}
                <nav className="p-3">
                    {Object.entries(groupedMenuItems).map(([category, items]) =>
                        renderCategorySection(category, items)
                    )}

                    {/* Show message if no menu items */}
                    {menuItems.length === 0 && (
                        <div className="text-center text-white-50 py-4">
                            <small>No menu items available</small>
                        </div>
                    )}
                </nav>
            </div>
        </div>
    );
};

export default Navigation;
