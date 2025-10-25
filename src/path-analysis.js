const { render, useState } = wp.element;
const { Button } = wp.components;

const PathAnalysis = () => {
    const { paths: pathData = [], total_paths: totalPaths = 0, paged: paged = 1, items_per_page: initialItemsPerPage = 50, site_url } = window.pathPilotPathData;
    let [itemsPerPage, setItemsPerPage] = useState(initialItemsPerPage);
    const currentPage = parseInt(paged, 10);

    itemsPerPage = +itemsPerPage;

    const renderPathIcons = (path) => {
        return path.map((step, index) => {
            const iconClass = step.is_home ? 'dashicons-admin-home' : 'dashicons-admin-page';
            return <a href={step.permalink} key={index} title={step.title} style={{textDecoration: 'none'}}><span className={`dashicons ${iconClass}`} style={{margin: '0 2px', color: '#9ca3af'}}></span></a>;
        });
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
        <div className="path-pilot-path-analysis">
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
                        <tr key={index}>
                            <td>
                                {renderPathIcons(row.path)}
                            </td>
                            <td>{row.steps}</td>
                            <td>{row.count}</td>
                            <td><span className="dashicons dashicons-calendar-alt" style={{marginRight: '5px'}}></span>{row.last_taken}</td>
                        </tr>
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
