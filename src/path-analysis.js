const { render, useState } = wp.element;
const { Button } = wp.components;

const PathAnalysis = () => {
    const hardcodedData = [
        { path: '/contact', steps: 6, lastTaken: '2 days ago', icons: ['home', 'document', 'document', 'document', 'document'] },
        { path: '/landing-page-name', steps: 5, lastTaken: 'Yesterday', icons: ['home', 'document', 'document', 'document', 'document', 'document'] },
        { path: '/path6-url-that-is-longer...', steps: 5, lastTaken: '17 days ago', icons: ['home', 'document-alt'] },
        { path: '/demo', steps: 3, lastTaken: '30 days ago', icons: ['home', 'document', 'document', 'document'] },
        { path: '/path6-url-that-is-longer...', steps: 3, lastTaken: '26 days ago', icons: ['home'] },
        { path: '/landing-page-name', steps: 3, lastTaken: '3 days ago', icons: ['home', 'document', 'document', 'document', 'document'] },
        { path: '/landing-page-name', steps: 3, lastTaken: '6 days ago', icons: ['home', 'document', 'document', 'document', 'document', 'document'] },
        { path: '/landing-page-name', steps: 10, lastTaken: '102 days ago', icons: ['home', 'document', 'document', 'document', 'document', 'document'] },
        { path: '/path6-url-that-is-longer...', steps: 3, lastTaken: 'Today', icons: ['home'] },
        { path: '/path6-url-that-is-longer...', steps: 3, lastTaken: '48 days ago', icons: ['home', 'document', 'document', 'document'] },
    ];

    const renderPathIcons = (icons) => {
        return icons.map((icon, index) => {
            let dashiconClass = 'dashicons-media-default';
            if (icon === 'home') {
                dashiconClass = 'dashicons-admin-home';
            } else if (icon === 'document-alt') {
                dashiconClass = 'dashicons-format-status';
            }
            return <span key={index} className={`dashicons ${dashiconClass}`} style={{margin: '0 2px', color: '#9ca3af'}}></span>;
        });
    };

    return (
        <div className="path-pilot-path-analysis">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '20px' }}>
                <div>
                    <h1 className="wp-heading-inline" style={{marginBottom: '10px'}}>Goal Path Analysis</h1>
                    <p style={{ margin: 0, color: '#50575e' }}>
                        <span className="dashicons dashicons-admin-site" style={{color: 'red', fontSize: '16px', marginRight: '5px'}}></span>
                        soliddigital.com Showing paths for the last <strong>30 days</strong>
                    </p>
                </div>
                <Button isPrimary style={{background: '#4CAF50', border: 'none'}}>
                    <span className="dashicons dashicons-plus" style={{marginRight: '5px'}}></span>
                    423 Goal Paths
                </Button>
            </div>

            <div className="pp-content" style={{backgroundColor: 'white', padding: '20px'}}>
                <table className="wp-list-table widefat">
                    <thead>
                    <tr>
                        <th scope="col" className="manage-column">Path</th>
                        <th scope="col" className="manage-column">Path Steps</th>
                        <th scope="col" className="manage-column">Path Last Taken</th>
                    </tr>
                    </thead>
                    <tbody>
                    {hardcodedData.map((row, index) => (
                        <tr key={index}>
                            <td>
                                {renderPathIcons(row.icons)}
                                {row.path}
                            </td>
                            <td>{row.steps}</td>
                            <td><span className="dashicons dashicons-calendar-alt" style={{marginRight: '5px'}}></span>{row.lastTaken}</td>
                        </tr>
                    ))}
                    </tbody>
                </table>
                <div style={{ display: 'flex', justifyContent: 'flex-end', alignItems: 'center', marginTop: '10px', color: '#50575e' }}>
                    <span>1-10 of 423</span>
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
