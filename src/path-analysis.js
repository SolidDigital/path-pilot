const { render, useState, Fragment } = wp.element;
const { Button } = wp.components;

const Tooltip = ({ content, position }) => {
    if (!content) {
        return null;
    }

    const style = {
        position: 'absolute',
        top: position.y,
        left: position.x,
        backgroundColor: '#23282d',
        color: '#fff',
        padding: '10px',
        borderRadius: '4px',
        zIndex: 100,
        maxWidth: '350px',
        lineHeight: '1.5',
        fontSize: '13px',
        boxShadow: '0 2px 5px rgba(0,0,0,0.2)',
    };

    return (
        <div style={style}>
            <div style={{ marginBottom: '5px' }}><strong>Name:</strong> {content.title}</div>
            <div style={{ marginBottom: '5px' }}><strong>URL:</strong> {content.permalink}</div>
            <div style={{ marginBottom: '5px' }}><strong>Post Type:</strong> {content.post_type}</div>
            {content.taxonomies && content.taxonomies.length > 0 && (
                <div>
                    <strong>Taxonomy:</strong>
                    <ul style={{ margin: '5px 0 0 20px', padding: 0, listStyleType: 'disc' }}>
                        {content.taxonomies.map((tax, i) => (
                            <li key={i}>{tax}</li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
};


const PathAnalysis = () => {
    const { paths: pathData = [], total_paths: totalPaths = 0, paged: paged = 1, items_per_page: initialItemsPerPage = 50, site_url } = window.pathPilotPathData;
    let [itemsPerPage, setItemsPerPage] = useState(initialItemsPerPage);
    const [expandedRow, setExpandedRow] = useState(null);
    const [tooltip, setTooltip] = useState({ visible: false, content: null, position: { x: 0, y: 0 } });
    const currentPage = parseInt(paged, 10);

    itemsPerPage = +itemsPerPage;

    const handleRowClick = (index) => {
        setExpandedRow(expandedRow === index ? null : index);
    };

    const handleMouseEnter = (e, step) => {
        const rect = e.target.getBoundingClientRect();
        const containerRect = e.target.closest('.path-pilot-path-analysis').getBoundingClientRect();
        setTooltip({
            visible: true,
            content: step,
            position: {
                x: rect.left - containerRect.left,
                y: rect.bottom - containerRect.top + 5
            },
        });
    };

    const handleMouseLeave = () => {
        setTooltip({ visible: false, content: null, position: { x: 0, y: 0 } });
    };

    const renderPathIcons = (path) => {
        const maxPermalinkLength = 50;
        const nodes = [];

        const renderStep = (step, isLast, key) => {
            const iconClass = step.is_home ? 'dashicons-admin-home' : 'dashicons-admin-page';
            if (isLast) {
                return (
                    <a href={step.permalink} target="_blank" key={key} style={{textDecoration: 'none'}}
                       onMouseEnter={(e) => handleMouseEnter(e, step)}
                       onMouseLeave={handleMouseLeave}
                       onClick={(e) => e.stopPropagation()}
                    >
                        {step.permalink.length > maxPermalinkLength ? step.permalink.substring(0, maxPermalinkLength) + '...' : step.permalink}
                    </a>
                );
            }
            return (
                <a href={step.permalink} target="_blank" key={key} style={{textDecoration: 'none'}}
                   onMouseEnter={(e) => handleMouseEnter(e, step)}
                   onMouseLeave={handleMouseLeave}
                   onClick={(e) => e.stopPropagation()}
                >
                    <span className={`dashicons ${iconClass}`} style={{margin: '0 2px', color: '#9ca3af'}}></span>
                </a>
            );
        };

        const renderArrow = (key) => {
            return <span key={key} className="dashicons dashicons-arrow-right-alt" style={{margin: '0 2px', color: '#9ca3af', opacity: 0.5}}></span>;
        };

        const renderEllipsis = (key) => {
            return <span key={key} className="dashicons dashicons-ellipsis" style={{margin: '0 2px', color: '#9ca3af'}}></span>;
        }

        if (path.length >= 8) {
            // First item
            nodes.push(renderStep(path[0], false, 'step-0'));
            nodes.push(renderArrow('arrow-0'));

            // Ellipsis
            nodes.push(renderEllipsis('ellipsis'));
            nodes.push(renderArrow('arrow-ellipsis'));

            // Last 5 items
            for (let i = path.length - 5; i < path.length; i++) {
                const isLast = i === path.length - 1;
                nodes.push(renderStep(path[i], isLast, `step-${i}`));
                if (!isLast) {
                    nodes.push(renderArrow(`arrow-${i}`));
                }
            }
        } else {
            path.forEach((step, index) => {
                const isLast = index === path.length - 1;
                nodes.push(renderStep(step, isLast, `step-${index}`));
                if (!isLast) {
                    nodes.push(renderArrow(`arrow-${index}`));
                }
            });
        }

        return nodes;
    };

    const handleViewChange = (e) => {
        const newItemsPerPage = parseInt(e.target.value, 10);
        setItemsPerPage(newItemsPerPage);
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'path-pilot-path-analysis');
        url.searchParams.set('paged', '1');
        url.searchParams.set('items', newItemsPerPage);
        window.location.href = url.href;
    };

    const getPageLink = (pageNumber) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'path-pilot-path-analysis');
        url.searchParams.set('paged', pageNumber);
        url.searchParams.set('items', itemsPerPage);
        return url.href;
    };

    const totalPages = Math.ceil(totalPaths / itemsPerPage);
    const startItem = (currentPage - 1) * itemsPerPage + 1;
    const endItem = Math.min(startItem + itemsPerPage - 1, totalPaths);
    return (
        <div className="path-pilot-path-analysis" style={{ position: 'relative' }}>
            {tooltip.visible && <Tooltip content={tooltip.content} position={tooltip.position} />}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '20px' }}>
                <div>
                    <h1 className="wp-heading-inline" style={{marginBottom: '10px'}}>Goal Path Analysis</h1>
                    <p style={{ margin: 0, color: '#50575e' }}>
                        <span className="dashicons dashicons-admin-site" style={{color: 'red', fontSize: '16px', marginRight: '5px'}}></span>
                        {site_url.replace(/https?:\/\//, '')} Showing paths for the last <strong>30 days</strong>
                    </p>
                </div>
                <Button isPrimary style={{background: '#4CAF50', border: 'none'}}>
                    <span className="dashicons dashicons-plus" style={{marginRight: '5px'}}></span>
                    {totalPaths} Goal Paths
                </Button>
            </div>

            <div className="pp-content" style={{backgroundColor: 'white', padding: '20px'}}>
                <table className="wp-list-table widefat">
                    <thead>
                    <tr>
                        <th scope="col" className="manage-column">Path</th>
                        <th scope="col" className="manage-column">Path Steps</th>
                        <th scope="col" className="manage-column">Count</th>
                        <th scope="col" className="manage-column">Path Last Taken</th>
                    </tr>
                    </thead>
                    <tbody>
                    {pathData.map((row, index) => (
                        <Fragment key={index}>
                            <tr onClick={() => handleRowClick(index)} style={{cursor: 'pointer'}}>
                                <td>
                                    {renderPathIcons(row.path)}
                                </td>
                                <td>{row.steps}</td>
                                <td>{row.count}</td>
                                <td><span className="dashicons dashicons-calendar-alt" style={{marginRight: '5px'}}></span>{row.last_taken}</td>
                            </tr>
                            {expandedRow === index && (
                                <tr className="path-pilot-expanded-row">
                                    <td colSpan="4">
                                        <ol style={{margin: '10px 0 10px 20px'}}>
                                            {row.path.map((step, stepIndex) => (
                                                <li key={stepIndex} style={{marginBottom: '5px'}}>
                                                    <a href={step.permalink} target="_blank">
                                                        {step.permalink.replace(site_url, '')}
                                                    </a>
                                                </li>
                                            ))}
                                        </ol>
                                    </td>
                                </tr>
                            )}
                        </Fragment>
                    ))}
                    </tbody>
                </table>
                <div style={{ display: 'flex', justifyContent: 'flex-end', alignItems: 'center', marginTop: '10px', color: '#50575e' }}>
                    <span>{startItem}-{endItem} of {totalPaths}</span>
                    {currentPage > 1 ? (
                        <a href={getPageLink(currentPage - 1)} className="button is-small" style={{marginLeft: '15px'}}>&lt;</a>
                    ) : (
                        <span className="button is-small is-disabled" style={{marginLeft: '15px'}}>&lt;</span>
                    )}
                    {currentPage < totalPages ? (
                        <a href={getPageLink(currentPage + 1)} className="button is-small" style={{marginLeft: '5px'}}>&gt;</a>
                    ) : (
                        <span className="button is-small is-disabled" style={{marginLeft: '5px'}}>&gt;</span>
                    )}
                    <span style={{marginLeft: '20px'}}>View</span>
                    <select value={itemsPerPage} onChange={handleViewChange} style={{marginLeft: '5px'}}>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
            </div>

            <div className="pp-content" style={{ marginTop: '40px', backgroundColor: 'white', padding: '20px' }}>
                <h3>Need clarification?</h3>
                <p><strong>Path</strong> = The path taken to Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
                <p><strong>Path Steps</strong> = The path taken to Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
                <p><strong>Path Last Taken</strong> = The path taken to Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
            </div>
        </div>
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const rootEl = document.getElementById('path-pilot-path-analysis-root');
    if (rootEl) {
        render(<PathAnalysis />, rootEl);
    }
});
