//
// The app for the Food Market
//
function ciniki_foodmarket_main() {
    this.pricePercentToggles = {'0.00':'0%', '0.10':'10%', '0.20':'20%', '0.25':'25%', '0.30':'30%', '0.40':'40%', '0.50':'50%', '0.75':'75%', '1.00':'100%'};
    this.weightFlags = {'2':{'name':'lb'}, '3':{'name':'oz'}, '6':{'name':'kg'}, '7':{'name':'g'}};
    this.unitFlags = {'9':{'name':'Each'}, '10':{'name':'Pair'}, '11':{'name':'Bunch'}, '12':{'name':'Bag'}};
    this.caseFlags = {'17':{'name':'Case'}, '18':{'name':'Bushel'}, };

    //
    // Food Market
    //
    this.menu = new M.panel('Food Market', 'ciniki_foodmarket_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.menu');
    this.menu.date_id = 0;
    this.menu.category_id = '';
    this.menu.customer_id = 0;
    this.menu.nplist = [];
    this.menu.sections = {
        '_tabs':{'label':'', 'type':'menutabs', 'selected':'dates', 'tabs':{
            'dates':{'label':'Dates', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"dates");'},
            'products':{'label':'Products', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"products");'},
//            'inventory':{'label':'Inventory', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"inventory");'},
            'favourites':{'label':'Favourites', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"favourites");'},
            'suppliers':{'label':'Suppliers', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"suppliers");'},
            }},
/*        'supplier_products':{'label':'Products', 'type':'simplegrid', 'num_cols':2, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='suppliers'?'yes':'no';},
            'headerValues':['Category', 'Name'],
            'cellClasses':['', ''],
            'sortTypes':['text', 'text'],
            'noData':'No products',
            'addTxt':'Add Product',
            'addFn':'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',0);',
            }, */
        '_dates':{'label':'Change Date', 'aside':'yes',
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'dates') ? 'yes':'no'; },
            'fields':{
                'date_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'onchangeFn':'M.ciniki_foodmarket_main.menu.switchDate', 
                    'complex_options':{'name':'display_name', 'value':'id'}, 'options':{},
                    },
            }},
        'date_products':{'label':'Available Products', 'aside':'yes', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'dates') ? 'yes':'no'; },
            'headerValues':['Supplier', 'Product', ''],
            'cellClasses':['', '', 'alignright'],
            'noData':'No products available for this date',
            },
        'date_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':4, 'hint':'Search',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='dates'?'yes':'no';},
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright'],
            'noData':'No products found',
            },
        'recent_products':{'label':'Recent', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'dates') ? 'yes':'no'; },
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright'],
            'noData':'No recent date limited products',
            },
        'dated_products':{'label':'Dated Products', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'dates') ? 'yes':'no'; },
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright'],
            'noData':'No date limited products',
            },
        'categories':{'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'products') ? 'yes':'no'; },
            'cellClasses':['', 'alignright'],
            'addTxt':'Add Category',
            'addFn':'M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.menu.open();\',0);',
            },
        'product_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='products'?'yes':'no';},
            'cellClasses':['multiline'],
            'hint':'Search products', 
            'noData':'No products found',
            },
        'products':{'label':'Products', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='products'?'yes':'no';},
            'headerValues':['Supplier', 'Name', 'Types'],
            'cellClasses':['', '', ''],
            'sortTypes':['text', 'text', 'text'],
            'noData':'No Products',
            'addTxt':'Add Product',
            'addFn':'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',0,null,null,M.ciniki_foodmarket_main.menu.category_id);',
            },
        'productinputs':{'label':'Inventory', 'type':'simplegrid', 'num_cols':4, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='inventory'?'yes':'no';},
            'headerValues':['Category', 'Product', 'Option', 'Inventory'],
            'cellClasses':['', '', '', ''],
            'sortTypes':['text', 'text', 'text', 'number'],
            'noData':'No Products',
            },
        'suppliers':{'label':'Suppliers', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='suppliers'?'yes':'no';},
            'cellClasses':['', ''],
            'headerValues':['Code', 'Supplier', '# of Products'],
            'sortTypes':['text', 'text', 'number'],
            'noData':'No supplier',
            'addTxt':'Add Supplier',
            'addFn':'M.ciniki_foodmarket_main.supplier.open(\'M.ciniki_foodmarket_main.menu.open();\',0);',
            },
        'customers':{'label':'Customers', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { var t=M.ciniki_foodmarket_main.menu.sections._tabs.selected; return (t=='favourites') ? 'yes':'no'; },
            'noData':'No customers.',
            },
        'favourite_items':{'label':'Favourites', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'favourites' && M.ciniki_foodmarket_main.menu.customer_id == 0 ) ? 'yes':'no'; },
            'headerValues':['Item', '# Customers'],
            'noData':'No favourites',
            },
        'customer_favourites':{'label':'Favourites', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'favourites' && M.ciniki_foodmarket_main.menu.customer_id > 0 ) ? 'yes':'no'; },
            'headerValues':['Item', '# Orders'],
            'sortable':'yes', 'sortTypes':['text', 'number'],
            'noData':'No favourites for customer',
            },
    };
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.fieldValue = function(s, i, d) {
        return this.date_id;
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'date_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.dateItemSearch', {'business_id':M.curBusinessID, 'search_str':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.menu.liveSearchShow('date_search',null,M.gE(M.ciniki_foodmarket_main.menu.panelUID + '_' + s), rsp.products);
                });
        }
        if( s == 'product_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.productSearch', {'business_id':M.curBusinessID, 'search_str':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.menu.liveSearchShow('product_search',null,M.gE(M.ciniki_foodmarket_main.menu.panelUID + '_' + s), rsp.products);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'date_search' ) { 
            return this.dateProductCellValue(s, i, j, d);
        }
        if( s == 'product_search' ) { 
            return d.name;
        }
    }
    this.menu.dateProductCellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.supplier_code;
            case 1: return d.name;
            case 2: return d.last_order_date;
            case 3: return '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.dateItemAdd(event,"' + d.id + '");return false;\'>Add</button>';
        }
        return '';
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'date_search' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\');';
        }
        if( s == 'product_search' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\');';
        }
    }
    this.menu.rowClass = function(s, i, d) {
        if( s == 'customers' && this.customer_id == d.id ) {
            return 'highlight';
        }
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'date_products' ) {
            switch(j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.dateItemDelete(event,"' + d.id + '");return false;\'>Delete</button>';
            }
        }
        if( s == 'recent_products' || s == 'dated_products' ) {
            return this.dateProductCellValue(s, i, j, d);
        }
        if( s == 'suppliers' ) {
            switch(j) {
                case 0: return d.code;
                case 1: return d.name;
                case 2: return d.num_products;
            }
        } else if( s == 'categories' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return (d.num_products != null && d.num_products > 0 ? ' <span class="count">' + d.num_products + '</span>' : '');
            }
        } else if( s == 'products' || s == 'supplier_products' ) {
            switch (j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return d.input_names;
            }
        } else if( s == 'customers' ) {
            if( d.num_items != null && d.num_items != '' ) {
                return d.display_name + ' <span class="count">' + d.num_items + '</span>';
            }
            return d.display_name;
        } else if( s == 'favourite_items' ) {
            switch(j) {
                case 0: return d.description;
                case 1: return d.num_customers;
            }
        } else if( s == 'customer_favourites' ) {
            switch(j) {
                case 0: return d.description;
                case 1: return d.num_orders;
            }
        }
    };
    this.menu.rowFn = function(s, i, d) {
        if( s == 'date_products' || s == 'recent_products' || s == 'dated_products' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\');';
        } 
        if( s == 'categories' ) {
            return 'M.ciniki_foodmarket_main.menu.open(null, \'products\', \'' + d.id + '\',\'' + escape(d.fullname) + '\');';
        } 
        if( s == 'suppliers' ) {
            return 'M.ciniki_foodmarket_main.supplier.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\');';
        } 
        if( s == 'products' || s == 'supplier_products' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\',null,M.ciniki_foodmarket_main.menu.nplist);';
        } 
        if( s == 'customers' ) {
            return 'M.ciniki_foodmarket_main.menu.openFavourites(\'' + d.id + '\');';
        }
        return '';
    };
    this.menu.dateItemAdd = function(e, oid) {
        M.api.getJSONCb('ciniki.foodmarket.dateItemAdd', {'business_id':M.curBusinessID, 'date_id':this.date_id, 'output_id':oid, 'date_products':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.menu;
            p.data.date_products = rsp.date_products;
            p.refreshSection('date_products');
        });
    }
    this.menu.dateItemDelete = function(e, oid) {
        M.api.getJSONCb('ciniki.foodmarket.dateItemDelete', {'business_id':M.curBusinessID, 'date_id':this.date_id, 'output_id':oid, 'date_products':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.menu;
            p.data.date_products = rsp.date_products;
            p.refreshSection('date_products');
        });
    }
    this.menu.openFavourites = function(cid) {
        this.customer_id = cid; 
        this.customer_name = '';
        if( M.ciniki_foodmarket_main.menu.data.customers != null ) {
            for(var i in M.ciniki_foodmarket_main.menu.data.customers) {
                if( M.ciniki_foodmarket_main.menu.data.customers[i].id == this.customer_id ) {
                    this.customer_name = M.ciniki_foodmarket_main.menu.data.customers[i].display_name;
                }
            }
        }
        this.open();
    }
    this.menu.switchDate = function(s, i) {
        this.date_id = this.formValue(i);
        this.order_id = 0;
        this.open();
    }
    this.menu.openDates = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.data = rsp;
        p.nplists = [];
        if( rsp.nplists != null ) {
            p.nplists = rsp.nplists;
        }
        p.sections._dates.fields.date_id.options = rsp.dates;
        if( rsp.date_id != null && rsp.date_id > 0 ) {
            p.date_id = rsp.date_id;
        }
        p.refresh();
        p.show();
    }
    this.menu.open = function(cb, tab, itab, title) {
        this.data = {};
        if( cb != null ) { this.cb = cb; }
        if( tab != null ) { this.sections._tabs.selected = tab; }
        if( itab != null && this.sections._tabs.selected == 'products' ) { this.category_id = itab; }
        if( title != null && this.sections._tabs.selected == 'products' ) { this.sections.products.label = unescape(title); }
        if( this.category_id == 0 ) { this.sections.products.label = 'Uncategorized'; }
        if( this.category_id == '' ) { this.sections.products.label = 'Latest'; }
        if( this.sections._tabs.selected == 'dates' ) {
            this.size = 'medium mediumaside';
            args['categories'] = 'yes';
            M.api.getJSONCb('ciniki.foodmarket.dateItems', {'business_id':M.curBusinessID, 'date_id':this.date_id}, M.ciniki_foodmarket_main.menu.openDates);
        } else if( this.sections._tabs.selected == 'inventory' ) {
            this.size = 'large narrowaside';
/*            M.api.getJSONCb('ciniki.foodmarket.supplierList', {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_foodmarket_main.menu;
                p.data = rsp;
                if( rsp.nextprevlist != null ) {
                    p.nplist = rsp.nextprevlist;
                }
                p.delButton('edit');
                if( p.category_id > 0 && p.sections._tabs.selected == 'products' ) {
                    p.addButton('edit', 'Edit', 'M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + p.category_id + '\');');
                }
                p.refresh();
                p.show(cb);
            }); */
        } else if( this.sections._tabs.selected == 'products' ) {
            this.size = 'medium narrowaside';
            args['categories'] = 'yes';
            M.api.getJSONCb('ciniki.foodmarket.productList', {'business_id':M.curBusinessID, 'categories':'yes', 'category_id':this.category_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_foodmarket_main.menu;
                p.data = rsp;
                if( rsp.nextprevlist != null ) {
                    p.nplist = rsp.nextprevlist;
                }
                p.delButton('edit');
                if( p.category_id > 0 && p.sections._tabs.selected == 'products' ) {
                    p.addButton('edit', 'Edit', 'M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + p.category_id + '\');');
                }
                p.refresh();
                p.show(cb);
            });
        } else if( this.sections._tabs.selected == 'suppliers' ) {
            this.size = 'medium';
            M.api.getJSONCb('ciniki.foodmarket.supplierList', {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_foodmarket_main.menu;
                p.data = rsp;
                if( rsp.nplist != null ) {
                    p.nplist = rsp.nplist;
                }
                p.refresh();
                p.show(cb);
            });
        } else if( this.sections._tabs.selected == 'favourites' ) {
            this.size = 'medium narrowaside';
            M.api.getJSONCb('ciniki.foodmarket.favouriteList', {'business_id':M.curBusinessID, 'customers':'yes', 'customer_id':this.customer_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_foodmarket_main.menu;
                p.size = 'medium narrowaside';
                p.data = rsp;
                p.data.customers.unshift({'id':'0', 'display_name':'All Customers'});
                if( p.customer_id > 0 ) {
                    p.sections.customer_favourites.label = p.customer_name;
                } else {
                    p.sections.customer_favourites.label = 'Favourites';
                }
                p.refresh();
                p.show(cb);
            });
        } else {
            this.size = 'large';
        }
/*        args = {'business_id':M.curBusinessID};
        method = '';
        switch( this.sections._tabs.selected ) {
            case 'suppliers': method = 'ciniki.foodmarket.supplierList'; break;
            case 'products': method = 'ciniki.foodmarket.productList'; break;
            case 'favourites': method = 'ciniki.foodmarket.favouriteList'; break;
        }
        if( this.sections._tabs.selected == 'products' ) {
            if( this.category_id != '' ) {
                args['category_id'] = this.category_id;
            }
            args['categories'] = 'yes';
        }
        if( this.sections._tabs.selected == 'favourites' ) {
            args['customers'] = 'yes';
            args['customer_id'] = this.customer_id;
        }
        M.api.getJSONCb(method, args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.menu;
            p.data = rsp;
            if( rsp.nextprevlist != null ) {
                p.nplist = rsp.nextprevlist;
            }
            p.delButton('edit');
            if( p.category_id > 0 && p.sections._tabs.selected == 'products' ) {
                p.addButton('edit', 'Edit', 'M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + p.category_id + '\');');
            }
            p.refresh();
            p.show(cb);
        }); */
    };
    this.menu.addClose('Back');

    //
    // The panel for editing a product
    //
    this.product = new M.panel('Product', 'ciniki_foodmarket_main', 'product', 'mc', 'large narrowaside', 'sectioned', 'ciniki.foodmarket.main.product');
    this.product.data = {};
    this.product.product_id = 0;
    this.product.sections = { 
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_foodmarket_main.product.setFieldValue('primary_image_id', iid, null, null);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                        M.ciniki_foodmarket_main.product.setFieldValue(fid, 0, null, null);
                        return true;
                    },
                },
            }},
        'ptype':{'label':'', 'aside':'yes', 'type':'paneltabs', 'field_id':'ptype', 'selected':'10', 'tabs':{
            '10':{'label':'Supplied', 'fn':'M.ciniki_foodmarket_main.product.switchType(\'10\');'},
            '70':{'label':'Basket', 'fn':'M.ciniki_foodmarket_main.product.switchType(\'70\');'},
            }},
        '_supplier':{'label':'Supplier', 'aside':'yes',
            'visible':function() { return M.ciniki_foodmarket_main.product.sections.ptype.selected == '10' ? 'yes' : 'hidden';},
            'fields':{
                'supplier_id':{'label':'Supplier', 'hidelabel':'yes', 'type':'select', 'complex_options':{'name':'display_name', 'value':'id'}, 'options':{}},
            }},
        'general':{'label':'Product', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
//            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Private', '40':'Public', '90':'Archived'}},
//            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
           'packing_order':{'label':'Packing', 'type':'toggle', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
            }},
        'basket':{'label':'', 'aside':'yes', 
            'visible':function() { return M.ciniki_foodmarket_main.product.sections.ptype.selected == '70' ? 'yes' : 'hidden';},
            'fields':{
                'basket_retail_price':{'label':'Price', 'type':'text', 'size':'small'},
                'basket_retail_taxtype_id':{'label':'Tax', 'type':'select', 'options':{}},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'inputs', 'tabs':{
            'categories':{'label':'Categories', 
                'visible':function() {return M.modFlagSet('ciniki.foodmarket', 0x020);}, 
                'fn':'M.ciniki_foodmarket_main.product.selectTab("categories");'},
            'inputs':{'label':'Options', 
                'visible':function() { return M.ciniki_foodmarket_main.product.sections.ptype.selected == '10' ? 'yes' : 'no';},
                'fn':'M.ciniki_foodmarket_main.product.selectTab(\'inputs\');'},
//            'outputs':{'label':'Sell', 
//                'fn':'M.ciniki_foodmarket_main.product.selectTab(\'outputs\');'},
            'description':{'label':'Website', 'fn':'M.ciniki_foodmarket_main.product.selectTab("description");'},
            }},
        '_inputs':{'label':'', 'type':'paneltabs', 'selected':'input1', 
//            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs'); },
            'visible':'hidden',
            'tabs':{
                'input1':{'label':'A', 'fn':'M.ciniki_foodmarket_main.product.switchInput(\'input1\');'},
                'input2':{'label':'B', 'fn':'M.ciniki_foodmarket_main.product.switchInput(\'input2\');'},
                'input3':{'label':'C', 'fn':'M.ciniki_foodmarket_main.product.switchInput(\'input3\');'},
            }},
        'input1':{'label':'Purchase', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1'); },
            'fields':{
                'input1_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_name':{'label':'Name', 'visible':'yes', 'type':'text'},
                'input1_itype':{'label':'Purchase by', 'type':'toggle', 'onchange':'M.ciniki_foodmarket_main.product.updatePanel', 
                    'toggles':{'10':'Weight', '20':'Weighted Units', '30':'Units', '50':'Case'},
                    },
                'input1_units1':{'label':'Pay by', 'type':'flagspiece', 'visible':'no', 'field':'input1_units', 'mask':0xff, 'toggle':'yes', 'join':'yes', 
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel', 'flags':this.weightFlags,
                    },
                'input1_units2':{'label':'Order by', 'type':'flagspiece', 'visible':'no', 'field':'input1_units', 'mask':0x0f00, 'toggle':'yes', 'join':'yes', 'flags':this.unitFlags},
                'input1_units3':{'label':'Order by', 'type':'flagspiece', 'visible':'no', 'field':'input1_units', 'mask':0x0f0000, 'toggle':'yes', 'join':'yes', 'flags':this.caseFlags,
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel', 
                    },
                'input1_flags2':{'label':'Inventory', 'type':'flagtoggle', 'field':'input1_flags', 'bit':0x02, 'default':'off',
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel', 
                    // 'on_fields':['input1_inventory'],
                    },
                'input1_inventory':{'label':'Inventory', 'type':'text', 'visible':'no', 'size':'small'},
                'input1_sku':{'label':'Sku/Code', 'type':'text', 'visible':'yes', 'size':'medium'},
                'input1_min_quantity':{'label':'Minimum Order', 'type':'text', 'size':'small'},
                'input1_inc_quantity':{'label':'Incremental Order', 'type':'text', 'size':'small'},
                'input1_case_cost':{'label':'Case Cost', 'type':'text', 'visible':'no', 'size':'small', 'onkeyupFn':'M.ciniki_foodmarket_main.product.updatePrices'},
                'input1_half_cost':{'label':'Half Case Cost', 'type':'text', 'visible':'no', 'size':'small'},
                'input1_unit_cost':{'label':'Unit Cost', 'type':'text', 'visible':'no', 'size':'small', 'onkeyupFn':'M.ciniki_foodmarket_main.product.updatePrices'},
                'input1_case_units':{'label':'Units/Case', 'type':'text', 'visible':'no', 'size':'small', 'onkeyupFn':'M.ciniki_foodmarket_main.product.updatePrices'},
                'input1_unit_cost_calc':{'label':'Cost/Unit', 'type':'text', 'visible':'no', 'size':'small', 'editable':'no', 'history':'no'},
            }},
        'input1_10':{'label':'', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['10']); },
            'fields':{
                'input1_10_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_10_status':{'label':'Sell by Weight', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_10_units1', 'input1_10_flags2', 'input1_10_retail_percent', 'input1_10_retail_price_calc'],
                    'on_sections':['input1_71'],
                    },
                'input1_10_units1':{'label':'Units', 'type':'flagspiece', 'visible':'no', 'field':'input1_10_units', 'mask':0xff, 'toggle':'yes', 'join':'yes', 
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel', 'flags':this.weightFlags,
                    },
//                'input1_10_units1':{'label':'Units', 'type':'flagspiece', 'visible':'no', 'field':'input1_10_units', 'mask':0xff, 'toggle':'yes', 'join':'yes', 'flags':this.weightFlags},
                'input1_10_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_10_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
//                'input1_10_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_10_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_10_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_71':{'label':'',    
            'visible':function() { 
                if( M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['10']) == 'yes' && M.ciniki_foodmarket_main.product.formValue('input1_10_status') != '5' ) {
                    return 'yes';
                } else { 
                    return 'hidden'; 
                }},
            'fields':{
                'input1_71_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_71_status':{'label':'Basket', 'type':'toggle', 'visible':'yes', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_71_retail_discount', 'input1_71_retail_price_calc'],
                    },
//                'input1_71_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_71_retail_discount':{'label':'Discount', 'type':'toggle', 'visible':'no', 'default':'40', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 
                    'toggles':{'0':'0%', '0.05':'5%', '0.10':'10%', '0.15':'15%'},
                    },
                'input1_71_units':{'label':'', 'visible':'no', 'type':'text'},
                'input1_71_retail_percent':{'label':'', 'visible':'no', 'type':'text'},
                'input1_71_retail_price_calc':{'label':'Basket Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_30':{'label':'', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['30','50']); },
            'fields':{
                'input1_30_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_30_status':{'label':'Sell by Unit', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_30_units2', 'input1_30_flags2', 'input1_30_retail_percent', 'input1_30_retail_price_calc'],
                    'on_sections':['input1_72'],
                    },
                'input1_30_units2':{'label':'Units', 'type':'flagspiece', 'visible':'no', 'field':'input1_30_units', 'mask':0x0f00, 'toggle':'yes', 'join':'yes', 'flags':this.unitFlags,
                    'onchange':'M.ciniki_foodmarket_main.product.updatePrices',
                    },
                'input1_30_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_30_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
//                'input1_30_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_30_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 
                    'toggles':this.pricePercentToggles,
                    },
                'input1_30_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_72':{'label':'', 
            'visible':function() { 
                if( M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['30','50']) == 'yes' && M.ciniki_foodmarket_main.product.formValue('input1_30_status') != '5' ) {
                    return 'yes';
                } else { 
                    return 'hidden'; 
                }},
            'fields':{
                'input1_72_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_72_status':{'label':'Basket', 'type':'toggle', 'visible':'yes', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_72_retail_discount', 'input1_72_retail_price_calc'],
                    },
//                'input1_72_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_72_retail_discount':{'label':'Discount', 'type':'toggle', 'visible':'no', 'default':'40', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 
                    'toggles':{'0':'0%', '0.05':'5%', '0.10':'10%', '0.15':'15%'},
                    },
                'input1_72_units':{'label':'', 'visible':'no', 'type':'text'},
                'input1_72_retail_percent':{'label':'', 'visible':'no', 'type':'text'},
                'input1_72_retail_price_calc':{'label':'Basket Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_20':{'label':'', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['20']); },
            'fields':{
                'input1_20_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_20_status':{'label':'Sell by Weighted Unit', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_20_units1', 'input1_20_units2', 'input1_20_flags2', 'input1_20_retail_percent', 'input1_20_retail_price_calc'],
                    },
                'input1_20_units1':{'label':'Charge by', 'type':'flagspiece', 'visible':'no', 'field':'input1_20_units', 'mask':0xff, 'toggle':'yes', 'join':'yes',
                    'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'flags':this.weightFlags,
                    }, 
                'input1_20_units2':{'label':'Order by', 'type':'flagspiece', 'visible':'no', 'field':'input1_20_units', 'mask':0x0f00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'each'}, '10':{'name':'pair'}, '11':{'name':'bunch'}, '12':{'name':'bag'}},
                    }, 
                'input1_20_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_20_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
//                'input1_20_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_20_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 
                    'toggles':this.pricePercentToggles,
                    },
                'input1_20_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_50':{'label':'', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['50']); },
            'fields':{
                'input1_50_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_50_status':{'label':'Sell by Case', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_50_flags2', 'input1_50_retail_percent', 'input1_50_retail_price_calc'],
                    },
                'input1_50_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_50_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
//                'input1_50_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_50_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_50_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_52':{'label':'', 
            'visible':function() {
                var cu = M.ciniki_foodmarket_main.product.formValue('input1_case_units');
                cu = (cu != null ? parseFloat(cu) : 0);
                if( M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['50']) == 'yes' && cu > 1 && (cu%2) == 0 ) {
                    return 'yes';
                }
                return 'hidden';
            },
            'fields':{
                'input1_52_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_52_status':{'label':'Sell by 1/2 case', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_52_name', 'input1_52_flags2', 'input1_52_retail_percent', 'input1_52_retail_price_calc'],
                    },
                'input1_52_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/2 case'},
                'input1_52_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_52_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
//                'input1_52_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_52_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_52_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_53':{'label':'', 
            'visible':function() {
                var cu = M.ciniki_foodmarket_main.product.formValue('input1_case_units');
                cu = (cu != null ? parseFloat(cu) : 0);
                if( M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['50']) == 'yes' && cu > 2 && (cu%3) == 0 ) {
                    return 'yes';
                }
                return 'hidden';
            },
            'fields':{
                'input1_53_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_53_status':{'label':'Sell by 1/3 case', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_53_name', 'input1_53_flags2', 'input1_53_retail_percent', 'input1_53_retail_price_calc'],
                    },
                'input1_53_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/3 case'},
//                'input1_53_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_53_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_53_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_53_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_53_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_54':{'label':'', 
            'visible':function() {
                var cu = M.ciniki_foodmarket_main.product.formValue('input1_case_units');
                cu = (cu != null ? parseFloat(cu) : 0);
                if( M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['50']) == 'yes' && cu > 3 && (cu%4) == 0 ) {
                    return 'yes';
                }
                return 'hidden';
            },
            'fields':{
                'input1_54_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_54_status':{'label':'Sell by 1/4 case', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_54_name', 'input1_54_flags2', 'input1_54_retail_percent', 'input1_54_retail_price_calc'],
                    },
                'input1_54_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/4 case'},
                'input1_54_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_54_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
//                'input1_54_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_54_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_54_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_55':{'label':'', 
            'visible':function() {
                var cu = M.ciniki_foodmarket_main.product.formValue('input1_case_units');
                cu = (cu != null ? parseFloat(cu) : 0);
                if( M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['50']) == 'yes' && cu > 4 && (cu%5) == 0 ) {
                    return 'yes';
                }
                return 'hidden';
            },
            'fields':{
                'input1_55_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_55_status':{'label':'Sell by 1/5 case', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_55_name', 'input1_55_flags2', 'input1_55_retail_percent', 'input1_55_retail_price_calc'],
                    },
                'input1_55_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/5 case'},
                'input1_55_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_55_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
//                'input1_55_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_55_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_55_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        'input1_56':{'label':'', 
            'visible':function() {
                var cu = M.ciniki_foodmarket_main.product.formValue('input1_case_units');
                cu = (cu != null ? parseFloat(cu) : 0);
                if( M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['50']) == 'yes' && cu > 5 && (cu%6) == 0 ) {
                    return 'yes';
                }
                return 'hidden';
            },
            'fields':{
                'input1_56_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_56_status':{'label':'Sell by 1/6 case', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_56_name', 'input1_56_flags2', 'input1_56_retail_percent', 'input1_56_retail_price_calc'],
                    },
                'input1_56_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/6 case'},
                'input1_56_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_56_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
//                'input1_56_packing_order':{'label':'Packing', 'type':'toggle', 'visible':'no', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
                'input1_56_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_56_retail_price_calc':{'label':'Price', 'type':'text', 'visible':'no', 'editable':'no'},
            }},
        '_categories':{'label':'Categories', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('categories'); },
            'addTxt':'Add Category',
            'addFn':'M.ciniki_foodmarket_main.product.save("M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.product.refreshCategories();\',0,M.ciniki_foodmarket_main.product.product_id);");',
            'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':[], 'hint':'Enter a new category: '},
            }},
        '_synopsis':{'label':'Synopsis', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('description'); },
            'fields':{
                'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_description':{'label':'Description', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('description'); },
            'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_ingredients':{'label':'Ingredients', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('description'); },
            'fields':{
                'ingredients':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'medium', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.product.save();'},
            'delete':{'label':'Delete', 'visible':function() {return M.ciniki_foodmarket_main.product.product_id>0?'yes':'no';}, 'fn':'M.ciniki_foodmarket_main.product.remove();'},
            }},
        };  
    this.product.sectionData = function(s) { 
        return this.data[s];
    }
    this.product.fieldValue = function(s, i, d) { return this.data[i]; }
    this.product.liveSearchCb = function(s, i, value) {
        if( i == 'category' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.productSearchField', {'business_id':M.curBusinessID, 'field':i, 'start_needle':value, 'limit':15},
                function(rsp) {
                    M.ciniki_foodmarket_main.product.liveSearchShow(s, i, M.gE(M.ciniki_foodmarket_main.product.panelUID + '_' + i), rsp.results);
                });
        }
    };
    this.product.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'category' && d != null ) { return d.name; }
        return '';
    };
    this.product.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( f == 'category' && d != null ) {
            return 'M.ciniki_foodmarket_main.product.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.name) + '\');';
        }
    };
    this.product.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
    this.product.fieldHistoryArgs = function(s, i) {
        if( s == 'input1' ) {
//            if( this.sections[s].fields[i].field != null ) {
//                return {'method':'ciniki.foodmarket.productInputHistory', 'args':{'business_id':M.curBusinessID, 'input_id':this.data.input1_id, 'field':this.sections[s].fields[i].field.replace(/input1_/, '')}};
//            } else {
                return {'method':'ciniki.foodmarket.productInputHistory', 'args':{'business_id':M.curBusinessID, 'input_id':this.data.input1_id, 'field':i.replace(/input1_/,'')}};
//            }
        }
        return {'method':'ciniki.foodmarket.productHistory', 'args':{'business_id':M.curBusinessID, 'product_id':this.product_id, 'field':i}};
    }
    this.product.cellValue = function(s, i, j, d) {
        if( s == 'inputs' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.supplier_price_display;
                case 2: return d.wholesale_price_display;
                case 3: return d.basket_price_display;
                case 4: return d.retail_price_display;
                case 5: return d.inventory;
            }
        }
        if( s == 'outputs' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.supplier_price_display;
                case 2: return d.wholesale_price_display;
                case 3: return d.basket_price_display;
                case 4: return d.retail_price_display;
                case 5: return d.inventory;
            }
        }
    }
    this.product.addDropImage = function(iid) {
        if( this.product_id == 0 ) {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.foodmarket.productAdd', {'business_id':M.curBusinessID, 'product_id':this.product_id, 'image_id':iid}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_foodmarket_main.product.product_id = rsp.id;
                    M.ciniki_foodmarket_main.product.refreshImages();
                });
        } else {
            M.api.getJSONCb('ciniki.foodmarket.productImageAdd', {'business_id':M.curBusinessID, 'image_id':iid, 'name':'', 'product_id':this.product_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.product.refreshImages();
            });
        }
        return true;
    };
/*    this.product.rowFn = function(s, i, d) {
        if( s == 'inputs' ) {
            return 'M.ciniki_foodmarket_main.productinput.open(\'M.ciniki_foodmarket_main.product.refreshInputs();\',' + d.id + ');';
        } else if( s == 'outputs' ) {
            return 'M.ciniki_foodmarket_main.productoutput.open(\'M.ciniki_foodmarket_main.product.refreshOutputs();\',' + d.id + ');';
        }
    } */
//    this.product.refreshInputs = function() { this.reloadSection('inputs');}
//    this.product.refreshOutputs = function() { this.reloadSection('outputs');}
    this.product.inputVisible = function(tab, input, itype) {
        if( tab != this.sections._tabs.selected ) { return 'hidden'; }
        if( input != null && input != this.sections._inputs.selected ) { return 'hidden'; }
        if( itype != null ) {
            var v = this.formValue(input + '_itype');
            if( v == 0 || itype.indexOf(v) < 0 ) { return 'hidden'; }
        }
        return 'yes';
    }
    this.product.reloadSection = function(section) {
        M.api.getJSONCb('ciniki.foodmarket.productGet', {'business_id':M.curBusinessID, 'product_id':this.product_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.product;
            p.data[section] = rsp.product[section];
            p.refreshSection(section);
            p.show();
        });
    };
    this.product.refreshCategories = function() {
        M.api.getJSONCb('ciniki.foodmarket.productGet', {'business_id':M.curBusinessID, 'product_id':this.product_id, 'categories':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.product;
            p.sections._categories.fields.categories.list = rsp.categories;
            p.refreshSection('_categories');
            p.show();
        });
    };
    this.product.switchType = function(type) {
        this.sections.ptype.selected = type;
        if( this.sections._tabs.selected == 'inputs' ) {
            this.sections._tabs.selected = 'categories';
        }
        this.refreshSections(['ptype', '_tabs', '_inputs']);
        this.showHideSections(['_supplier', 'basket']);
        this.showHideSections(['_categories', '_synopsis', '_description', '_ingredients']);
//        this.showHideInputs();
        this.updatePanel();
    }
    this.product.selectTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.refreshSections(['_tabs', '_inputs']);
        this.showHideSections(['_categories', '_synopsis', '_description', '_ingredients']);
        this.updatePanel();
//        this.updateInput('input1');
//        this.showHideInputs();
//        this.updatePrices();
    };
    this.product.switchInput = function(i) {
        this.sections._inputs.selected = i;
        this.refreshSection('_inputs');
        this.updatePanel();
    }
    this.product.updatePanel = function() {
        for(var i = 1;i < 2;i++) {      // FIXME: Add when more inputs
            this.updateInput('input' + i);
        }
        this.updatePrices();
    }
    this.product.updatePrices = function(s, fid) {
        for(var i = 1;i < 3;i++) {
            var itype = this.formValue('input' + i + '_itype');
            var unitcost = this.formValue('input' + i + '_unit_cost');
            var casecost = this.formValue('input' + i + '_case_cost');
            var caseunits = this.formValue('input' + i + '_case_units');
            var ipu = this.formValue('input' + i + '_units1');
            if( itype == '50' && casecost !== null && casecost != '' && caseunits !== null && caseunits != '' ) {
                ipu = this.formValue('input' + i + '_units3');
                // Ignore form field unit_cost
                if( typeof casecost == 'string' ) {
                    casecost = parseFloat(casecost.replace(/\$/, ''));
                }
                unitcost = (casecost/parseFloat(caseunits));
                this.data['input' + i + '_unit_cost_calc'] = '$' + unitcost.toFixed(2) + '/unit';

                //
                // Calculate the retail price full case
                //
                var rp50 = this.formValue('input' + i + '_50_retail_percent');
                if( rp50 != '' && casecost !== null && casecost != '' && casecost > 0 ) {  
                    casecost = parseFloat(casecost);
                    rp50 = parseFloat(rp50);
                    var ppu = (casecost * (1+rp50)) / (caseunits);
                    this.data['input' + i + '_50_retail_price_calc'] = '$' + (casecost * (1+rp50)).toFixed(2) + M.ciniki_foodmarket_main.unitSuffix(ipu&0x0f0000)
                        + ' ($' + ppu.toFixed(2) + '/unit)';
                } else {
                    this.data['input' + i + '_50_retail_price_calc'] = '';
                }
                var divisors = [2,3,4,5,6];
                for(var j in divisors) {
                    //
                    // Calculate the retail price 1/j case
                    //
                    var rp = this.formValue('input' + i + '_5' + divisors[j] + '_retail_percent');
                    if( rp != '' && casecost != '' && casecost > 0 ) {  
                        rp = parseFloat(rp);
                        var ppu = ((casecost/divisors[j]) * (1+rp)) / (caseunits/divisors[j]);
                        this.data['input' + i + '_5' + divisors[j] + '_retail_price_calc'] = '$' + ((casecost/divisors[j]) * (1+rp)).toFixed(2)  
                            + ' per 1/' + divisors[j] + ' ' + M.ciniki_foodmarket_main.unitText(ipu&0x0f0000)
                            + ' ($' + ppu.toFixed(2) + '/unit)';
                    } else {
                        this.data['input' + i + '_5' + divisors[j] + '_retail_price_calc'] = '';
                    }
                    this.refreshFormField('input' + i + '_5' + divisors[j], 'input' + i + '_5' + divisors[j] + '_retail_price_calc');
                }
            } else {
                this.data['input' + i + '_unit_cost_calc'] = '';
            }
            if( unitcost !== null && unitcost != '' ) {
                if( typeof unitcost == 'string' ) {
                    unitcost = parseFloat(unitcost.replace(/\$/, ''));
                }
                //
                // Calculate the retail price for sell by weight based on percent
                //
                var rp10 = this.formValue('input' + i + '_10_retail_percent');
                var opu = this.formValue('input' + i + '_10_units1');   // output weight type
                if( rp10 != '' && ipu != '' && ipu > 0 && opu != '' && opu > 0 ) {  
                    rp10 = parseFloat(rp10);
                    var auc = M.ciniki_foodmarket_main.convertWeightPrice(unitcost, (ipu&0xff), (opu&0xff)); // Adjust the unit price from input units to output units
                    this.data['input' + i + '_10_retail_price_calc'] = '$' + (auc * (1+rp10)).toFixed(2) + M.ciniki_foodmarket_main.unitSuffix(opu);
                    // Calculate basket retail price based on retail price and basket discount
                    var rd71 = this.formValue('input' + i + '_71_retail_discount');
                    if( rd71 != '' ) {
                        rd71 = parseFloat(rd71);
                        rp71 = (1+rp10) - ((1+rp10)*rd71) - 1;
                        if( rp71 < 0 ) {
                            rp71 = 0;               // Can't be below zero, otherwise below cost
                        }
                        this.data['input' + i + '_71_retail_price_calc'] = '$' + (auc * (1+rp71)).toFixed(2) + M.ciniki_foodmarket_main.unitSuffix(opu);
                        this.setFieldValue('input' + i + '_71_retail_percent', rp71.toFixed(6), 0, 0);
                        this.setFieldValue('input' + i + '_71_units', opu, 0, 0);
                    } else {
                        this.data['input' + i + '_71_retail_price_calc'] = '';
                        this.setFieldValue('input' + i + '_71_retail_percent', 0, 0, 0);
                        this.setFieldValue('input' + i + '_71_units', opu, 0, 0);
                    }
                } else {
                    this.data['input' + i + '_10_retail_price_calc'] = '';
                    this.data['input' + i + '_71_retail_price_calc'] = '';
                    this.setFieldValue('input' + i + '_71_retail_percent', 0, 0, 0);
                    this.setFieldValue('input' + i + '_71_units', 0, 0, 0);
                }
                //
                // Calculate the retail price for sell by weighted unit based on percent
                //
                var rp20 = this.formValue('input' + i + '_20_retail_percent');
                var opu = this.formValue('input' + i + '_20_units1');   // output weight type
                if( rp20 != '' && ipu != '' && ipu > 0 && opu != '' && opu > 0 ) {  
                    rp20 = parseFloat(rp20);
                    var auc = M.ciniki_foodmarket_main.convertWeightPrice(unitcost, (ipu&0xff), (opu&0xff)); // Adjust the unit price from input units to output units
                    this.data['input' + i + '_20_retail_price_calc'] = '$' + (auc * (1+rp20)).toFixed(2) + M.ciniki_foodmarket_main.unitSuffix(opu);
                } else {
                    this.data['input' + i + '_20_retail_price_calc'] = '';
                }
                //
                // Calculate the retail price for sell by unit base on percent
                //
                var rp30 = this.formValue('input' + i + '_30_retail_percent');
                var opu = this.formValue('input' + i + '_30_units2');   // output weight type
                if( rp30 != '' ) {  
                    rp30 = parseFloat(rp30);
                    this.data['input' + i + '_30_retail_price_calc'] = '$' + (unitcost * (1+rp30)).toFixed(2) + M.ciniki_foodmarket_main.unitSuffix(opu&0xff00);
                    // Calculate basket retail price based on retail price and basket discount
                    var rd72 = this.formValue('input' + i + '_72_retail_discount');
                    if( rd72 != '' ) {
                        rd72 = parseFloat(rd72);
                        rp72 = (1+rp30) - ((1+rp30)*rd72) - 1;
                        if( rp72 < 0 ) {
                            rp72 = 0;               // Can't be below zero, otherwise below cost
                        }
                        this.data['input' + i + '_72_retail_price_calc'] = '$' + (unitcost * (1+rp72)).toFixed(2) + M.ciniki_foodmarket_main.unitSuffix(opu&0xff00);
                        this.setFieldValue('input' + i + '_72_retail_percent', rp72.toFixed(6), 0, 0);
                        this.setFieldValue('input' + i + '_72_units', opu, 0, 0);
                    } else {
                        this.data['input' + i + '_72_retail_price_calc'] = '';
                        this.setFieldValue('input' + i + '_72_retail_percent', 0, 0, 0);
                        this.setFieldValue('input' + i + '_72_units', opu, 0, 0);
                    }
                } else {
                    this.data['input' + i + '_30_retail_price_calc'] = '';
                    this.data['input' + i + '_72_retail_price_calc'] = '';
                    this.setFieldValue('input' + i + '_72_retail_percent', 0, 0, 0);
                    this.setFieldValue('input' + i + '_72_units', 0, 0, 0);
                }
            }

            this.showHideSection('input' + i + '_52');
            this.showHideSection('input' + i + '_53');
            this.showHideSection('input' + i + '_54');
            this.showHideSection('input' + i + '_55');
            this.showHideSection('input' + i + '_56');
            this.refreshFormField('input' + i, 'input' + i + '_unit_cost_calc');
            this.refreshFormField('input' + i + '_10', 'input' + i + '_10_retail_price_calc');
            this.refreshFormField('input' + i + '_20', 'input' + i + '_20_retail_price_calc');
            this.refreshFormField('input' + i + '_30', 'input' + i + '_30_retail_price_calc');
            this.refreshFormField('input' + i + '_50', 'input' + i + '_50_retail_price_calc');
            this.refreshFormField('input' + i + '_71', 'input' + i + '_71_retail_price_calc');
            this.refreshFormField('input' + i + '_72', 'input' + i + '_72_retail_price_calc');
        }
    }
    this.product.updateInput = function(s) {
        // Check if panel is displayed or not
        if( M.gE(this.panelUID + '_' + s) == null ) {
            var v = this.fieldValue(s + '_itype');
            var u1 = this.fieldValue(s + '_units1');
            var u2 = this.fieldValue(s + '_units2');
            var flags = this.fieldValue(s + '_flags');
            this.sections[s].fields[s + '_inventory'].visible = ((flags&0x02) == 0x02 ? 'yes' : 'no');
        } else {
            var v = this.formValue(s + '_itype');
            var u1 = this.formValue(s + '_units1');
            var u2 = this.formValue(s + '_units2');
            var flags = this.formValue(s + '_flags2');
            this.sections[s].fields[s + '_inventory'].visible = (flags == 'on' ? 'yes' : 'no');
        }
        this.sections[s].fields[s + '_units1'].visible = 'no';
        this.sections[s].fields[s + '_units2'].visible = 'no';
        this.sections[s].fields[s + '_units3'].visible = 'no';
        this.sections[s].fields[s + '_case_cost'].visible = 'no';
        this.sections[s].fields[s + '_half_cost'].visible = 'no';
        this.sections[s].fields[s + '_unit_cost'].visible = 'no';
        this.sections[s].fields[s + '_case_units'].visible = 'no';
        this.sections[s].fields[s + '_unit_cost_calc'].visible = 'no';
        if( v == '10' ) {   // Weight
            this.sections[s].fields[s + '_units1'].visible = 'yes';
            if( u1 > 0 ) {
                this.sections[s].fields[s + '_unit_cost'].label = 'Cost/' + M.ciniki_foodmarket_main.unitText(u1);
                this.sections[s].fields[s + '_unit_cost'].visible = 'yes';
            }
        } else if( v == '20' ) { // Weighted Unit
            this.sections[s].fields[s + '_units1'].visible = 'yes';
            this.sections[s].fields[s + '_units2'].visible = 'yes';
            if( u1 > 0 ) {
                this.sections[s].fields[s + '_unit_cost'].label = 'Cost/' + M.ciniki_foodmarket_main.unitText(u1);
                this.sections[s].fields[s + '_unit_cost'].visible = 'yes';
            }
        } else if( v == '30' ) { // Units
            this.sections[s].fields[s + '_units2'].visible = 'yes';
            this.sections[s].fields[s + '_unit_cost'].label = 'Cost/Unit';
            this.sections[s].fields[s + '_unit_cost'].visible = 'yes';
        } else if( v == '50' ) { // Cases
            this.sections[s].fields[s + '_units3'].visible = 'yes';
            this.sections[s].fields[s + '_case_cost'].visible = 'yes';
            this.sections[s].fields[s + '_half_cost'].visible = 'yes';
            this.sections[s].fields[s + '_case_units'].visible = 'yes';
            this.sections[s].fields[s + '_unit_cost_calc'].visible = 'yes';
        }
        this.showHideFormField(s, s + '_inventory');
        this.showHideFormField(s, s + '_units1');
        this.showHideFormField(s, s + '_units2');
        this.showHideFormField(s, s + '_units3');
        this.showHideFormField(s, s + '_case_cost');
        this.showHideFormField(s, s + '_half_cost');
        this.showHideFormField(s, s + '_unit_cost');
        this.showHideFormField(s, s + '_case_units');
        this.showHideFormField(s, s + '_unit_cost_calc');
        this.showHideSection(s);
        this.showHideOutputs(s);
    }
    this.product.showHideOutputs = function(s) {
        var outputs = [s + '_10', s + '_20', s + '_30', s + '_50', s + '_52', s + '_53', s + '_54', s + '_55', s + '_56', s + '_71', s + '_72'];
        for(var i in outputs) {
            this.showHideOutput(outputs[i]);
        }
    }
    this.product.showHideOutput = function(s) {
        for(var i in this.sections[s].fields) {
            if( this.sections[s].fields[i].on_fields != null ) {
                var visible = 'no';
                if( M.gE(this.panelUID + '_' + s) == null ) {
                    if( this.fieldValue(i) >= 10 ) { 
                        visible = 'yes';
                    }
                } else if( this.formValue(i) >= 10 ) { 
                    visible = 'yes';
                }
                if( this.sections[s].fields[i].on_fields != null ) {
                    for(var j in this.sections[s].fields[i].on_fields) {
                        var f = this.formField(this.sections[s].fields[i].on_fields[j]);
                        f.visible = visible;
                        this.showHideFormField(s, this.sections[s].fields[i].on_fields[j]);
                    }
                }
                if( this.sections[s].fields[i].on_sections != null ) {
                    for(var j in this.sections[s].fields[i].on_sections) {
                        this.showHideSection(this.sections[s].fields[i].on_sections[j]);
                    }
                }
            }
        }
        this.showHideSection(s);
    }
    this.product.open = function(cb, id, tab, list, cid) {
        this.reset();
        if( id != null ) { this.product_id = id; }
        if( tab != null ) { this.product.sections._tabs.selected = tab; }
        if( list != null ) { this.nplist = list; }
        var args = {'business_id':M.curBusinessID, 'product_id':this.product_id, 'categories':'yes', 'suppliers':'yes'};
        if( cid != null ) { args.category_id = cid; }
        M.api.getJSONCb('ciniki.foodmarket.productGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.product;
            p.data = rsp.product;
            if( rsp.product.ptype != null && rsp.product.ptype > 0 ) {
                p.sections.ptype.selected = rsp.product.ptype;
                if( rsp.product.ptype > 10 && p.sections._tabs.selected == 'inputs' ) {
                    p.sections._tabs.selected = 'categories';
                }
            }
            p.sections._supplier.fields.supplier_id.options = rsp.suppliers;
            p.sections._categories.fields.categories.list = rsp.categories;
            p.refresh();
            p.show(cb);
            p.updatePanel();
        });
    }
    this.product.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.product.close();'; }
        if( this.product_id > 0 ) {
            var c = this.serializeForm('no');
            c += 'input1_id=' + this.formValue('input1_id') + '&';
            for(var i in {10:'', 20:'', 30:'', 50:'', 52:'', 53:'', 54:'', 55:'', 56:'', 71:'', 72:''}) {
                if( this.formValue('input1_' + i + '_id') == '0' ) {
                    c += 'input1_' + i + '_id=' + this.formValue('input1_' + i + '_id') + '&';
                } else {
                    c += this.serializeFormSection('yes', 'input1_' + i);
                }
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.foodmarket.productUpdate', {'business_id':M.curBusinessID, 'product_id':this.product_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.foodmarket.productAdd', {'business_id':M.curBusinessID, 'product_id':this.product_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_foodmarket_main.product.product_id = rsp.id;
                    eval(cb);
                });
        }
    };
    this.product.remove = function() {
        if( confirm('Are you sure you want to remove this product?') ) {
            M.api.getJSONCb('ciniki.foodmarket.productDelete', {'business_id':M.curBusinessID, 'product_id':this.product_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_foodmarket_main.product.close();
            });
        }
    };
    this.product.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.product_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_foodmarket_main.product.save(\'M.ciniki_foodmarket_main.product.open(null,' + this.nplist[this.nplist.indexOf('' + this.product_id) + 1] + ');\');';
        }
        return null;
    }
    this.product.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.product_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.product.save(\'M.ciniki_foodmarket_main.product.open(null,' + this.nplist[this.nplist.indexOf('' + this.product_id) - 1] + ');\');';
        }
        return null;
    }
    this.product.addButton('save', 'Save', 'M.ciniki_foodmarket_main.product.save();');
    this.product.addClose('Cancel');
    this.product.addButton('next', 'Next');
    this.product.addLeftButton('prev', 'Prev');

    //
    // The panel for editing a category or child category
    //
    this.category = new M.panel('Category', 'ciniki_foodmarket_main', 'category', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.foodmarket.main.category');
    this.category.data = {};
    this.category.category_id = 0;
    this.category.sections = { 
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_foodmarket_main.category.setFieldValue('primary_image_id', iid, null, null);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                        M.ciniki_foodmarket_main.category.setFieldValue(fid, 0, null, null);
                        return true;
                    },
                },
            }},
        'general':{'label':'Product', 'aside':'yes', 'fields':{
            'parent_id':{'label':'Parent', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            'name':{'label':'Name', 'type':'text'},
            'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
            'ctype':{'label':'Type', 'type':'toggle', 'default':'0', 'toggles':{'0':'Products', '10':'Favourites', '30':'Specials', '50':'New Products'}},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        'children':{'label':'Child Categories', 'type':'simplegrid', 'num_cols':1, 
            'visible':function() { return M.ciniki_foodmarket_main.category.data.children.length > 0 ? 'yes' : 'no'; },
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.category.save();'},
            'delete':{'label':'Delete', 'visible':function() {return M.ciniki_foodmarket_main.category.category_id>0?'yes':'no';}, 'fn':'M.ciniki_foodmarket_main.category.remove();'},
            }},
        };  
    this.category.fieldValue = function(s, i, d) { return this.data[i]; }
    this.category.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.foodmarket.categoryHistory', 'args':{'business_id':M.curBusinessID, 'category_id':this.category_id, 'field':i}};
    }
    this.category.cellValue = function(s, i, j, d) {
        if( s == 'children' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.category.open = function(cb, id, list) {
        this.reset();
        if( id != null ) { this.category_id = id; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.foodmarket.categoryGet', {'business_id':M.curBusinessID, 'category_id':this.category_id, 'parents':'yes', 'children':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.category;
            p.data = rsp.category;
            p.sections.general.fields.parent_id.options = rsp.parents;
            p.refresh();
            p.show(cb);
        });
    }
    this.category.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.category.close();'; }
        if( this.category_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.foodmarket.categoryUpdate', {'business_id':M.curBusinessID, 'category_id':this.category_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.foodmarket.categoryAdd', {'business_id':M.curBusinessID, 'category_id':this.category_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_foodmarket_main.category.category_id = rsp.id;
                    eval(cb);
                });
        }
    };
    this.category.remove = function() {
        if( confirm('Are you sure you want to remove this category?') ) {
            M.api.getJSONCb('ciniki.foodmarket.categoryDelete', {'business_id':M.curBusinessID, 'category_id':this.category_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_foodmarket_main.category.close();
            });
        }
    };
    this.category.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_foodmarket_main.category.save(\'M.ciniki_foodmarket_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) + 1] + ');\');';
        }
        return null;
    }
    this.category.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.category.save(\'M.ciniki_foodmarket_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) - 1] + ');\');';
        }
        return null;
    }
    this.category.addButton('save', 'Save', 'M.ciniki_foodmarket_main.category.save();');
    this.category.addClose('Cancel');
    this.category.addButton('next', 'Next');
    this.category.addLeftButton('prev', 'Prev');

    //
    // The supplier edit panel
    //
    this.supplier = new M.panel('Supplier', 'ciniki_foodmarket_main', 'supplier', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.foodmarket.main.supplier');
    this.supplier.data = null;
    this.supplier.supplier_id = 0;
    this.supplier.sections = { 
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'general':{'label':'General', 'aside':'yes', 'fields':{
            'code':{'label':'Code', 'hint':'supplier code or short name', 'type':'text', 'size':'small'},
            'name':{'label':'Name', 'hint':'supplier name', 'type':'text'},
            'category':{'label':'Category', 'hint':'', 'type':'text'},
            }}, 
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.supplier.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_foodmarket_main.supplier.remove();'},
            }},
        };  
    this.supplier.fieldValue = function(s, i, d) { return this.data[i]; }
    this.supplier.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.foodmarket.supplierHistory', 'args':{'business_id':M.curBusinessID, 'supplier_id':this.supplier_id, 'field':i}};
    }
    this.supplier.addDropImage = function(iid) {
        M.ciniki_foodmarket_main.supplier.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.supplier.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.supplier.open = function(cb, eid) {
        this.reset();
        if( eid != null ) { this.supplier_id = eid; }
        this.sections._buttons.buttons.delete.visible = (this.supplier_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.foodmarket.supplierGet', {'business_id':M.curBusinessID, 'supplier_id':this.supplier_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.supplier;
            p.data = rsp.supplier;
            p.refresh();
            p.show(cb);
        });
    };
    this.supplier.save = function() {
        if( this.supplier_id > 0 ) {
            var c = this.serializeForm('no');
            M.api.postJSONCb('ciniki.foodmarket.supplierUpdate', {'business_id':M.curBusinessID, 'supplier_id':this.supplier_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_foodmarket_main.supplier.close();
            });
        } else {
            var c = this.serializeForm('yes');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.foodmarket.supplierAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_foodmarket_main.supplier.close();
                });
            } else {
                this.close();
            }
        }
    };
    this.supplier.remove = function() {
        if( confirm("Are you sure you want to remove '" + this.data.name + "' as an supplier ?") ) {
            M.api.getJSONCb('ciniki.foodmarket.supplierDelete', {'business_id':M.curBusinessID, 'supplier_id':this.supplier_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.supplier.close();
            });
        }
    }
    this.supplier.addButton('save', 'Save', 'M.ciniki_foodmarket_main.supplier.save();');
    this.supplier.addClose('Cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_foodmarket_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Setup the taxtypes available for the business
        //
        if( M.curBusiness.modules['ciniki.taxes'] != null ) {
            this.product.sections.basket.fields.basket_retail_taxtype_id.active = 'yes';
            this.product.sections.basket.fields.basket_retail_taxtype_id.options = {'0':'No Taxes'};
            if( M.curBusiness.modules != null && M.curBusiness.modules['ciniki.taxes'] != null && M.curBusiness.modules['ciniki.taxes'].settings.types != null ) {
                for(i in M.curBusiness.modules['ciniki.taxes'].settings.types) {
                    this.product.sections.basket.fields.basket_retail_taxtype_id.options[M.curBusiness.modules['ciniki.taxes'].settings.types[i].type.id] = M.curBusiness.modules['ciniki.taxes'].settings.types[i].type.name;
                }
            }
        } else {
            this.product.sections.basket.fields.basket_retail_taxtype_id.active = 'no';
            this.product.sections.basket.fields.basket_retail_taxtype_id.options = {'0':'No Taxes'};
        }
        
        this.menu.open(cb,null,'');
    }

    this.unitText = function(u) {
        switch((u&0x66)) {
            case 0x02: return 'lb';
            case 0x04: return 'oz';
            case 0x20: return 'kg';
            case 0x40: return 'g';
        }
        switch((u&0x0f00)) {
            case 0x0100: return 'each';
            case 0x0200: return 'pair';
            case 0x0400: return 'bunch';
            case 0x0800: return 'bag';
        }
        switch((u&0x0f0000)) {
            case 0x010000: return 'case';
            case 0x020000: return 'bushel';
            case 0x040000: return '';
            case 0x080000: return '';
        }
    }
    this.unitSuffix = function(u) {
        switch((u&0x66)) {
            case 0x02: return '/lb';
            case 0x04: return '/oz';
            case 0x20: return '/kg';
            case 0x40: return '/g';
        }
        switch((u&0x0f00)) {
            case 0x0100: return ' each';
            case 0x0200: return '/pair';
            case 0x0400: return '/bunch';
            case 0x0800: return '/bag';
        }
        switch((u&0x0f0000)) {
            case 0x010000: return '/case';
            case 0x020000: return '/bushel';
            case 0x040000: return '';
            case 0x080000: return '';
        }
        return '';
    }
    this.convertWeightPrice = function(p, i, o) {
        if( i == o ) { return p; }
        // $/lb -> $/oz
        if( i == 0x02 && o == 0x04 ) { return p*0.0625; } 
        // $/lb -> $/kg
        else if( i == 0x02 && o == 0x20 ) { return p*2.20462; }
        // $/lb -> $/g
        else if( i == 0x02 && o == 0x40 ) { return p*0.00220462; }

        // $/oz -> $/lb
        else if( i == 0x04 && o == 0x02 ) { return p*16; }
        // $/oz -> $/kg
        else if( i == 0x04 && o == 0x20 ) { return p*35.274; }
        // $/oz -> $/g
        else if( i == 0x04 && o == 0x40 ) { return p*0.035274; }

        // $/kg -> $/lb
        else if( i == 0x20 && o == 0x02 ) { return p*0.453592; }
        // $/kg -> $/oz
        else if( i == 0x20 && o == 0x04 ) { return p*0.0283495; }
        // $/kg -> $/g
        else if( i == 0x20 && o == 0x40 ) { return p*0.001; }

        // $/g -> $/lb
        else if( i == 0x40 && o == 0x02 ) { return p*453.492; }
        // $/g -> $/oz
        else if( i == 0x40 && o == 0x04 ) { return p*28.3495; }
        // $/g -> $/kg
        else if( i == 0x40 && o == 0x20 ) { return p*1000; }
    }
}
