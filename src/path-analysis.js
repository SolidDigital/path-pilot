const { render, useState } = wp.element;
const { Button } = wp.components;

const PathAnalysis = () => {
    const pathData = window.pathPilotPathData.paths || [];

    const renderPathIcons = (path) => {
        return path.map((step, index) => {
            return <a href={step.permalink} key={index} title={step.title} style={{textDecoration: 'none'}}><span className="dashicons dashicons-admin-page" style={{margin: '0 2px', color: '#9ca3af'}}></span></a>;
        });
    };

    return (
        <div className="path-pilot-path-analysis">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '20px' }}>
                <div>
                    <h1 className="wp-heading-inline" style={{marginBottom: '10px'}}>Goal Path Analysis</h1>
                    <p style={{ margin: 0, color: '#50575e' }}>
                        <span className="dashicons dashicons-admin-site" style={{color: 'red', fontSize: '16px', marginRight: '5px'}}></span>
                        {window.pathPilotPathData.site_url.replace(/https?:\/\//, '')} Showing paths for the last <strong>30 days</strong>
                    </p>
                </div>
                <Button isPrimary style={{background: '#4CAF50', border: 'none'}}>
                    <span className="dashicons dashicons-plus" style={{marginRight: '5px'}}></span>
                    {pathData.length} Goal Paths
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
                    <span>1-{pathData.length} of {pathData.length}</span>
                    <Button isSmall disabled style={{marginLeft: '15px'}}>&lt;</Button>
                    <Button isSmall style={{marginLeft: '5px'}}>&gt;</Button>
                    <span style={{marginLeft: '20px'}}>View</span>
                    <select style={{marginLeft: '5px'}}>
                        <option>20</option>
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
