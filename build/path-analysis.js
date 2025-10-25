/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!******************************!*\
  !*** ./src/path-analysis.js ***!
  \******************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__);

const {
  render,
  useState,
  Fragment
} = wp.element;
const {
  Button
} = wp.components;
const Tooltip = ({
  content,
  position
}) => {
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
    boxShadow: '0 2px 5px rgba(0,0,0,0.2)'
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    style: style,
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      style: {
        marginBottom: '5px'
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("strong", {
        children: "Name:"
      }), " ", content.title]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      style: {
        marginBottom: '5px'
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("strong", {
        children: "URL:"
      }), " ", content.permalink]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      style: {
        marginBottom: '5px'
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("strong", {
        children: "Post Type:"
      }), " ", content.post_type]
    }), content.taxonomies && content.taxonomies.length > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("strong", {
        children: "Taxonomy:"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("ul", {
        style: {
          margin: '5px 0 0 20px',
          padding: 0,
          listStyleType: 'disc'
        },
        children: content.taxonomies.map((tax, i) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("li", {
          children: tax
        }, i))
      })]
    })]
  });
};
const PathAnalysis = () => {
  const {
    paths: pathData = [],
    total_paths: totalPaths = 0,
    paged = 1,
    items_per_page: initialItemsPerPage = 50,
    site_url
  } = window.pathPilotPathData;
  let [itemsPerPage, setItemsPerPage] = useState(initialItemsPerPage);
  const [expandedRow, setExpandedRow] = useState(null);
  const [tooltip, setTooltip] = useState({
    visible: false,
    content: null,
    position: {
      x: 0,
      y: 0
    }
  });
  const currentPage = parseInt(paged, 10);
  itemsPerPage = +itemsPerPage;
  const handleRowClick = index => {
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
      }
    });
  };
  const handleMouseLeave = () => {
    setTooltip({
      visible: false,
      content: null,
      position: {
        x: 0,
        y: 0
      }
    });
  };
  const renderPathIcons = path => {
    const maxPermalinkLength = 50;
    const nodes = [];
    const renderStep = (step, isLast, key) => {
      const iconClass = step.is_home ? 'dashicons-admin-home' : 'dashicons-admin-page';
      if (isLast) {
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("a", {
          href: step.permalink,
          title: step.title,
          style: {
            textDecoration: 'none'
          },
          onMouseEnter: e => handleMouseEnter(e, step),
          onMouseLeave: handleMouseLeave,
          children: step.permalink.length > maxPermalinkLength ? step.permalink.substring(0, maxPermalinkLength) + '...' : step.permalink
        }, key);
      }
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("a", {
        href: step.permalink,
        title: step.title,
        style: {
          textDecoration: 'none'
        },
        onMouseEnter: e => handleMouseEnter(e, step),
        onMouseLeave: handleMouseLeave,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
          className: `dashicons ${iconClass}`,
          style: {
            margin: '0 2px',
            color: '#9ca3af'
          }
        })
      }, key);
    };
    const renderArrow = key => {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        className: "dashicons dashicons-arrow-right-alt",
        style: {
          margin: '0 2px',
          color: '#9ca3af',
          opacity: 0.5
        }
      }, key);
    };
    const renderEllipsis = key => {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        className: "dashicons dashicons-ellipsis",
        style: {
          margin: '0 2px',
          color: '#9ca3af'
        }
      }, key);
    };
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
  const handleViewChange = e => {
    const newItemsPerPage = parseInt(e.target.value, 10);
    setItemsPerPage(newItemsPerPage);
    const url = new URL(window.location.href);
    url.searchParams.set('page', 'path-pilot-path-analysis');
    url.searchParams.set('paged', '1');
    url.searchParams.set('items', newItemsPerPage);
    window.location.href = url.href;
  };
  const getPageLink = pageNumber => {
    const url = new URL(window.location.href);
    url.searchParams.set('page', 'path-pilot-path-analysis');
    url.searchParams.set('paged', pageNumber);
    url.searchParams.set('items', itemsPerPage);
    return url.href;
  };
  const totalPages = Math.ceil(totalPaths / itemsPerPage);
  const startItem = (currentPage - 1) * itemsPerPage + 1;
  const endItem = Math.min(startItem + itemsPerPage - 1, totalPaths);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    className: "path-pilot-path-analysis",
    style: {
      position: 'relative'
    },
    children: [tooltip.visible && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Tooltip, {
      content: tooltip.content,
      position: tooltip.position
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      style: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: '20px'
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h1", {
          className: "wp-heading-inline",
          style: {
            marginBottom: '10px'
          },
          children: "Goal Path Analysis"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("p", {
          style: {
            margin: 0,
            color: '#50575e'
          },
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
            className: "dashicons dashicons-admin-site",
            style: {
              color: 'red',
              fontSize: '16px',
              marginRight: '5px'
            }
          }), site_url.replace(/https?:\/\//, ''), " Showing paths for the last ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("strong", {
            children: "30 days"
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(Button, {
        isPrimary: true,
        style: {
          background: '#4CAF50',
          border: 'none'
        },
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
          className: "dashicons dashicons-plus",
          style: {
            marginRight: '5px'
          }
        }), totalPaths, " Goal Paths"]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: "pp-content",
      style: {
        backgroundColor: 'white',
        padding: '20px'
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("table", {
        className: "wp-list-table widefat",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("thead", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("tr", {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("th", {
              scope: "col",
              className: "manage-column",
              children: "Path"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("th", {
              scope: "col",
              className: "manage-column",
              children: "Path Steps"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("th", {
              scope: "col",
              className: "manage-column",
              children: "Count"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("th", {
              scope: "col",
              className: "manage-column",
              children: "Path Last Taken"
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("tbody", {
          children: pathData.map((row, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(Fragment, {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("tr", {
              onClick: () => handleRowClick(index),
              style: {
                cursor: 'pointer'
              },
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("td", {
                children: renderPathIcons(row.path)
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("td", {
                children: row.steps
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("td", {
                children: row.count
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("td", {
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
                  className: "dashicons dashicons-calendar-alt",
                  style: {
                    marginRight: '5px'
                  }
                }), row.last_taken]
              })]
            }), expandedRow === index && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("tr", {
              className: "path-pilot-expanded-row",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("td", {
                colSpan: "4",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("ol", {
                  style: {
                    margin: '10px 0 10px 20px'
                  },
                  children: row.path.map((step, stepIndex) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("li", {
                    style: {
                      marginBottom: '5px'
                    },
                    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("a", {
                      href: step.permalink,
                      children: step.permalink.replace(site_url, '')
                    })
                  }, stepIndex))
                })
              })
            })]
          }, index))
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        style: {
          display: 'flex',
          justifyContent: 'flex-end',
          alignItems: 'center',
          marginTop: '10px',
          color: '#50575e'
        },
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("span", {
          children: [startItem, "-", endItem, " of ", totalPaths]
        }), currentPage > 1 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("a", {
          href: getPageLink(currentPage - 1),
          className: "button is-small",
          style: {
            marginLeft: '15px'
          },
          children: "<"
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
          className: "button is-small is-disabled",
          style: {
            marginLeft: '15px'
          },
          children: "<"
        }), currentPage < totalPages ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("a", {
          href: getPageLink(currentPage + 1),
          className: "button is-small",
          style: {
            marginLeft: '5px'
          },
          children: ">"
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
          className: "button is-small is-disabled",
          style: {
            marginLeft: '5px'
          },
          children: ">"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
          style: {
            marginLeft: '20px'
          },
          children: "View"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("select", {
          value: itemsPerPage,
          onChange: handleViewChange,
          style: {
            marginLeft: '5px'
          },
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("option", {
            value: "20",
            children: "20"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("option", {
            value: "50",
            children: "50"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("option", {
            value: "100",
            children: "100"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("option", {
            value: "250",
            children: "250"
          })]
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: "pp-content",
      style: {
        marginTop: '40px',
        backgroundColor: 'white',
        padding: '20px'
      },
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h3", {
        children: "Need clarification?"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("p", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("strong", {
          children: "Path"
        }), " = The path taken to Lorem Ipsum is simply dummy text of the printing and typesetting industry."]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("p", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("strong", {
          children: "Path Steps"
        }), " = The path taken to Lorem Ipsum is simply dummy text of the printing and typesetting industry."]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("p", {
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("strong", {
          children: "Path Last Taken"
        }), " = The path taken to Lorem Ipsum is simply dummy text of the printing and typesetting industry."]
      })]
    })]
  });
};
document.addEventListener('DOMContentLoaded', () => {
  const rootEl = document.getElementById('path-pilot-path-analysis-root');
  if (rootEl) {
    render(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(PathAnalysis, {}), rootEl);
  }
});
})();

/******/ })()
;
//# sourceMappingURL=path-analysis.js.map