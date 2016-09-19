//
// The app for the Food Market
//
function ciniki_foodmarket_main() {
    //
    // Food Market
    //
    this.products = new M.panel('Food Market', 'ciniki_foodmarket_main', 'products', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.products');
    this.products.category_id = 0;
    this.products.nextPrevList = [];
    this.products.sections = {
        '_tabs':{'label':'', 'type':'menutabs', 'selected':'products', 'tabs':{
            'products':{'label':'Products', 'fn':'M.ciniki_foodmarket_main.products.open(null,"products");'},
//            'inventory':{'label':'Inventory', 'fn':'M.ciniki_foodmarket_main.products.open(null,"inventory");'},
            'suppliers':{'label':'Suppliers', 'fn':'M.ciniki_foodmarket_main.products.open(null,"suppliers");'},
            }},
/*        'supplier_products':{'label':'Products', 'type':'simplegrid', 'num_cols':2, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.products.sections._tabs.selected=='suppliers'?'yes':'no';},
            'headerValues':['Category', 'Name'],
            'cellClasses':['', ''],
            'sortTypes':['text', 'text'],
            'noData':'No products',
            'addTxt':'Add Product',
            'addFn':'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.products.open();\',0);',
            }, */
        'categories':{'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_foodmarket_main.products.sections._tabs.selected == 'products') ? 'yes':'no'; },
            'cellClasses':['', 'alignright'],
            'addTxt':'Add Category',
            'addFn':'M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.products.open();\',0);',
            },
        'product_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 
            'visible':function() {return M.ciniki_foodmarket_main.products.sections._tabs.selected=='products'?'yes':'no';},
            'cellClasses':['multiline'],
            'hint':'Search products', 
            'noData':'No products found',
            },
        'products':{'label':'Products', 'type':'simplegrid', 'num_cols':2, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.products.sections._tabs.selected=='products'?'yes':'no';},
            'headerValues':['Name'],
            'cellClasses':[''],
            'sortTypes':['text'],
            'noData':'No Products',
            'addTxt':'Add Product',
            'addFn':'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.products.open();\',0);',
            },
        'productversions':{'label':'Inventory', 'type':'simplegrid', 'num_cols':4, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.products.sections._tabs.selected=='inventory'?'yes':'no';},
            'headerValues':['Category', 'Product', 'Option', 'Inventory'],
            'cellClasses':['', '', '', ''],
            'sortTypes':['text', 'text', 'text', 'number'],
            'noData':'No Products',
            },
        'suppliers':{'label':'Suppliers', 'type':'simplegrid', 'num_cols':2, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.products.sections._tabs.selected=='suppliers'?'yes':'no';},
            'cellClasses':['', ''],
            'headerValues':['Supplier', '# of Products'],
            'sortTypes':['text', 'number'],
            'noData':'No supplier',
            'addTxt':'Add Supplier',
            'addFn':'M.ciniki_foodmarket_main.supplier.open(\'M.ciniki_foodmarket_main.products.open();\',0);',
            },
    };
//    this.products.sectionData = function(s) {
//        return this.data[s];
//    };
    this.products.noData = function(s) { return this.sections[s].noData; }
    this.products.liveSearchCb = function(s, i, v) {
        if( s == 'product_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.productSearch', {'business_id':M.curBusinessID, 'search_str':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.products.liveSearchShow('product_search',null,M.gE(M.ciniki_foodmarket_main.products.panelUID + '_' + s), rsp.products);
                });
        }
    }
    this.products.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'product_search' ) { 
            return d.name;
        }
    }
    this.products.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'product_search' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.products.open();\',\'' + d.id + '\');';
        }
    }
    this.products.cellValue = function(s, i, j, d) {
        if( s == 'suppliers' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.num_products;
            }
        } else if( s == 'categories' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return (d.num_products != null && d.num_products > 0 ? ' <span class="count">' + d.num_products + '</span>' : '');
            }
        } else if( s == 'products' || s == 'supplier_products' ) {
            switch (j) {
                case 0: return d.name;
            }
        }
    };
    this.products.rowFn = function(s, i, d) {
        if( s == 'categories' ) {
            return 'M.ciniki_foodmarket_main.products.open(null, \'products\', \'' + d.id + '\',\'' + escape(d.fullname) + '\');';
        } else if( s == 'suppliers' ) {
            return 'M.ciniki_foodmarket_main.supplier.open(\'M.ciniki_foodmarket_main.products.open();\',\'' + d.id + '\');';
        } else if( s == 'products' || s == 'supplier_products' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.products.open();\',\'' + d.id + '\',null,M.ciniki_foodmarket_main.products.nextPrevList);';
        }
    };
    this.products.open = function(cb, tab, itab, title) {
        this.data = {};
        if( tab != null ) { this.sections._tabs.selected = tab; }
        if( itab != null && this.sections._tabs.selected == 'products' ) { this.category_id = itab; }
        if( title != null && this.sections._tabs.selected == 'products' ) { this.sections.products.label = unescape(title); }
        if( this.category_id == 0 ) { this.sections.products.label = 'Uncategorized'; }
        if( this.category_id == '' ) { this.sections.products.label = 'Latest'; }
        if( this.sections._tabs.selected == 'inventory' ) {
            this.size = 'large narrowaside';
        } else if( this.sections._tabs.selected == 'products' ) {
            this.size = 'medium narrowaside';
        } else if( this.sections._tabs.selected == 'suppliers' ) {
            this.size = 'medium';
        } else {
            this.size = 'large';
        }
        args = {'business_id':M.curBusinessID};
        method = '';
        switch( this.sections._tabs.selected ) {
            case 'suppliers': method = 'ciniki.foodmarket.supplierList'; break;
            case 'products': method = 'ciniki.foodmarket.productList'; break;
        }
        if( this.sections._tabs.selected == 'products' ) {
            if( this.category_id != '' ) {
                args['category_id'] = this.category_id;
            }
            args['categories'] = 'yes';
        }
        M.api.getJSONCb(method, args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.products;
            p.data = rsp;
            if( rsp.nextprevlist != null ) {
                p.nextPrevList = rsp.nextprevlist;
            }
            p.refresh();
            p.show(cb);
        });
    };
    this.products.addClose('Back');

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
        'general':{'label':'Product', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'supplier_id':{'label':'Supplier', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
//            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '90':'Archived'}},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'versions', 'tabs':{
            'versions':{'label':'Options', 'fn':'M.ciniki_foodmarket_main.product.selectTab("versions");'},
            'categories':{'label':'Categories', 'visible':function() {return M.modFlagSet('ciniki.foodmarket', 0x020);}, 'fn':'M.ciniki_foodmarket_main.product.selectTab("categories");'},
            'description':{'label':'Description', 'fn':'M.ciniki_foodmarket_main.product.selectTab("description");'},
            }},
        'versions':{'label':'Purchase Options', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return (M.ciniki_foodmarket_main.product.sections._tabs.selected == 'versions' ? 'yes':'hidden');},
            'headerValues':['Name', 'Supplier', 'Wholesale', 'Basket', 'Retail', 'Inventory'],
            'headerClasses':['', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright'],
            'cellClasses':['', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright'],
            'addTxt':'Add Option',
            'addFn':'M.ciniki_foodmarket_main.product.save("M.ciniki_foodmarket_main.productversion.open(\'M.ciniki_foodmarket_main.product.refreshVersions();\',0,M.ciniki_foodmarket_main.product.product_id);");',
            },
        '_categories':{'label':'Categories', 
            'visible':function() { return (M.ciniki_foodmarket_main.product.sections._tabs.selected == 'categories' ? 'yes':'hidden');},
            'addTxt':'Add Category',
            'addFn':'M.ciniki_foodmarket_main.product.save("M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.product.refreshCategories();\',0,M.ciniki_foodmarket_main.product.product_id);");',
            'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':[], 'hint':'Enter a new category: '},
            }},
        '_synopsis':{'label':'Synopsis', 
            'visible':function() { return (M.ciniki_foodmarket_main.product.sections._tabs.selected == 'description' ? 'yes':'hidden');},
            'fields':{
                'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_description':{'label':'Description', 
            'visible':function() { return (M.ciniki_foodmarket_main.product.sections._tabs.selected == 'description' ? 'yes':'hidden');},
            'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_ingredients':{'label':'Ingredients', 
            'visible':function() { return (M.ciniki_foodmarket_main.product.sections._tabs.selected == 'description' ? 'yes':'hidden');},
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
        return {'method':'ciniki.foodmarket.productHistory', 'args':{'business_id':M.curBusinessID, 
            'product_id':this.product_id, 'field':i}};
    }
    this.product.cellValue = function(s, i, j, d) {
        if( s == 'versions' ) {
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
    this.product.rowFn = function(s, i, d) {
        if( s == 'notes' ) {
            return 'M.ciniki_foodmarket_main.note.open(\'M.ciniki_foodmarket_main.recipe.updateNotes();\',\'' + d.id + '\');';
        } else {
            return 'M.ciniki_foodmarket_main.productversion.open(\'M.ciniki_foodmarket_main.product.refreshVersions();\',' + d.id + ');';
        }
    }
    this.product.refreshVersions = function() {
        M.api.getJSONCb('ciniki.foodmarket.productGet', {'business_id':M.curBusinessID, 'product_id':this.product_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.product;
            p.data.versions = rsp.product.versions;
            p.refreshSection('versions');
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
    this.product.selectTab = function(tab) {
        var p = M.ciniki_foodmarket_main.product;
        p.sections._tabs.selected = tab;
        p.refreshSection('_tabs');
        p.showHideSection('versions');
        p.showHideSection('_categories');
        p.showHideSection('_synopsis');
        p.showHideSection('_description');
        p.showHideSection('_ingredients');
    };
    this.product.open = function(cb, id, tab, list) {
        this.reset();
        if( id != null ) { this.product_id = id; }
        if( tab != null ) { this.product.sections._tabs.selected = tab; }
        if( list != null ) { this.nextPrevList = list; }
        M.api.getJSONCb('ciniki.foodmarket.productGet', {'business_id':M.curBusinessID, 'product_id':this.product_id, 'categories':'yes', 'suppliers':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.product;
            p.data = rsp.product;
            p.sections.general.fields.supplier_id.options = rsp.suppliers;
            p.sections._categories.fields.categories.list = rsp.categories;
            p.refresh();
            p.show(cb);
        });
    }
    this.product.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.product.close();'; }
        if( this.product_id > 0 ) {
            var c = this.serializeForm('no');
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
    this.product.updateNotes = function() {
        M.api.getJSONCb('ciniki.foodmarket.productGet', {'business_id':M.curBusinessID, 'product_id':this.product_id, 'notes':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.product;
            p.data.notes = rsp.product.notes;
            p.refreshSection('notes');
            p.show();
        });
    }
    this.product.nextButtonFn = function() {
        if( this.nextPrevList != null && this.nextPrevList.indexOf('' + this.product_id) < (this.nextPrevList.length - 1) ) {
            return 'M.ciniki_foodmarket_main.product.save(\'M.ciniki_foodmarket_main.product.open(null,' + this.nextPrevList[this.nextPrevList.indexOf('' + this.product_id) + 1] + ');\');';
        }
        return null;
    }
    this.product.prevButtonFn = function() {
        if( this.nextPrevList != null && this.nextPrevList.indexOf('' + this.product_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.product.save(\'M.ciniki_foodmarket_main.product.open(null,' + this.nextPrevList[this.nextPrevList.indexOf('' + this.product_id) - 1] + ');\');';
        }
        return null;
    }
    this.product.addButton('save', 'Save', 'M.ciniki_foodmarket_main.product.save();');
    this.product.addClose('Cancel');
    this.product.addButton('next', 'Next');
    this.product.addLeftButton('prev', 'Prev');

    //
    // The panel to display the edit form
    //
    this.productversion = new M.panel('Product Option', 'ciniki_foodmarket_main', 'productversion', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.productversion');
    this.productversion.data = {};
    this.productversion.productversion_id = 0;
    this.productversion.product_id = 0;
    this.productversion.sections = {
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'name':{'label':'Name', 'type':'text'},
//            'container_id':{'label':'Container', 'type':'select', 'options':{'0':'None'}, 'complex_options':{'name':'name', 'value':'id'}, 
//                'onchangeFn':'M.ciniki_foodmarket_main.productversion.updateCosts'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '90':'Archived'}},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            }},
        'options':{'label':'Options', 'fields':{
            'flags_1':{'label':'Visible', 'type':'flagtoggle', 'bit':0x01, 'field':'flags', 'default':'on'},
            'flags_4':{'label':'Available', 'type':'flagspiece', 'mask':0x38, 'field':'flags', 'toggle':'yes', 'flags':{'4':{'name':'Always'}, '5':{'name':'Weekly'}, '6':{'name':'Queued'}}},
            'flags_7':{'label':'Basket', 'type':'flagtoggle', 'bit':0x40, 'field':'flags', 'default':'off', 'on_fields':['basket_price']},
            'flags_2':{'label':'Inventory', 'type':'flagtoggle', 'bit':0x02, 'field':'flags', 'default':'off', 'on_fields':['inventory']},
            'inventory':{'label':'Inventory', 'active':'yes', 'type':'text', 'size':'small', 'active':'yes', 
                'visible':function() {return (M.ciniki_foodmarket_main.productversion.data.flags&0x02) > 0 ? 'yes' : 'no'}
                },
//            'flags_3':{'label':'Manufactured', 'type':'flagtoggle', 'bit':0x04, 'field':'flags', 'default':'off', 'on_fields':['recipe_id', 'recipe_quantity']},
//            'recipe_id':{'label':'Recipe', 'type':'select', 'options':{'0':'None'}, 'complex_options':{'name':'name', 'value':'id'}, 
//                'onchangeFn':'M.ciniki_foodmarket_main.productversion.updateCosts'},
//            'recipe_quantity':{'label':'Quantity', 'type':'text', 'size':'small', 'onkeyupFn':'M.ciniki_foodmarket_main.productversion.updateCosts'},
//        '_costs':{'label':'Cost/Container', 'fields':{
//            'materials_cost_per_container':{'label':'Materials', 'type':'text', 'editable':'no', 'history':'no'},
//            'time_cost_per_container':{'label':'Time', 'type':'text', 'editable':'no', 'history':'no'},
//            'total_cost_per_container':{'label':'Total', 'type':'text', 'editable':'no', 'history':'no'},
//            'total_time_per_container':{'label':'Seconds', 'type':'text', 'editable':'no', 'history':'no'},
//            }}, 
            }},
        '_prices':{'label':'Prices', 'fields':{
            'supplier_price':{'label':'Supplier', 'type':'text', 'size':'small'},
            'wholesale_price':{'label':'Wholesale', 'type':'text', 'size':'small'},
            'basket_price':{'label':'Basket', 'type':'text', 'size':'small', 'active':'yes', 
                'visible':function() {return (M.ciniki_foodmarket_main.productversion.data.flags&0x40) > 0 ? 'yes' : 'no'}
                },
            'retail_price':{'label':'Retail', 'type':'text', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.productversion.save();'},
            'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_foodmarket_main.productversion.remove();'},
            }},
    };
    this.productversion.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) {
            return this.data[i]; 
        } 
        return ''; 
    };
    this.productversion.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.foodmarket.productVersionHistory', 'args':{'business_id':M.curBusinessID, 
            'productversion_id':this.productversion_id, 'field':i}};
    };
/*    this.productversion.updateCosts = function() {
        var mc = 0;
        var tc = 0;
        var t = 0;
        var q = M.gE(this.panelUID + '_recipe_quantity').value;
        if( q > 0 && this.formValue('recipe_id') > 0 ) {
            var rid = this.formValue('recipe_id');
            for(var i in this.data.recipes) {
                if( this.data.recipes[i].id == rid ) {
                    mc += (parseFloat(this.data.recipes[i].materials_cost_per_unit) * q);
                    tc += (parseFloat(this.data.recipes[i].time_cost_per_unit) * q);
                    t += (parseFloat(this.data.recipes[i].total_time_per_unit) * q);
                }
            }
        }
        if( q > 0 && this.formValue('container_id') > 0 ) {
            var cid = this.formValue('container_id');
            for(var i in this.data.containers) {
                if( this.data.containers[i].id == cid ) {
                    mc += parseFloat(this.data.containers[i].cost_per_unit);
                }
            }
        }
        var c = mc + tc;
        M.gE(this.panelUID + '_materials_cost_per_container').value = '$' + mc.toFixed((mc>0&&mc<0.001)?4:(mc>0&&mc<0.01?3:2));
        M.gE(this.panelUID + '_time_cost_per_container').value = '$' + tc.toFixed((tc>0&&tc<0.001)?4:(tc>0&&tc<0.01?3:2));
        M.gE(this.panelUID + '_total_cost_per_container').value = '$' + c.toFixed((c>0&&c<0.001)?4:(c>0&&c<0.01?3:2));
        M.gE(this.panelUID + '_total_time_per_container').value = t.toFixed(3) + ' sec';
    }; */
    this.productversion.open = function(cb, iid, pid) {
        if( iid != null ) { this.productversion_id = iid; }
        if( pid != null ) { this.product_id = pid; }
        this.reset();
        this.sections._buttons.buttons.delete.visible = (this.productversion_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.foodmarket.productVersionGet', {'business_id':M.curBusinessID, 'productversion_id':this.productversion_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.productversion;
            p.data = rsp.productversion;
//            p.data.recipes = rsp.recipes;
//            p.data.containers = rsp.containers;
//            p.sections.info.fields.recipe_id.options = rsp.recipes;
//            p.sections.info.fields.container_id.options = rsp.containers;
            p.refresh();
            p.show(cb);
        });
    };
    this.productversion.save = function() {
        if( this.productversion_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.foodmarket.productVersionUpdate', {'business_id':M.curBusinessID, 
                    'productversion_id':this.productversion_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_foodmarket_main.productversion.close();
                        }
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONFormData('ciniki.foodmarket.productVersionAdd', {'business_id':M.curBusinessID, 'product_id':this.product_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_foodmarket_main.productversion.productversion_id = rsp.id;
                M.ciniki_foodmarket_main.productversion.close();
            });
        }
    };
    this.productversion.remove = function() {
        if( confirm('Are you sure you want to delete this purchase option?') ) {
            M.api.getJSONCb('ciniki.foodmarket.productVersionDelete', {'business_id':M.curBusinessID, 'productversion_id':this.productversion_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.productversion.close();
            });
        }
    };
    this.productversion.addButton('save', 'Save', 'M.ciniki_foodmarket_main.productversion.save();');
    this.productversion.addClose('Cancel');

    //
    // The panel for editing a category or child category
    //
    this.category = new M.panel('Category', 'ciniki_foodmarket_main', 'category', 'mc', 'medium narrowaside', 'sectioned', 'ciniki.foodmarket.main.category');
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
        if( list != null ) { this.nextPrevList = list; }
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
        if( this.nextPrevList != null && this.nextPrevList.indexOf('' + this.category_id) < (this.nextPrevList.length - 1) ) {
            return 'M.ciniki_foodmarket_main.category.save(\'M.ciniki_foodmarket_main.category.open(null,' + this.nextPrevList[this.nextPrevList.indexOf('' + this.category_id) + 1] + ');\');';
        }
        return null;
    }
    this.category.prevButtonFn = function() {
        if( this.nextPrevList != null && this.nextPrevList.indexOf('' + this.category_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.category.save(\'M.ciniki_foodmarket_main.category.open(null,' + this.nextPrevList[this.nextPrevList.indexOf('' + this.category_id) - 1] + ');\');';
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

        if( args.menu != null && args.menu == 'products' ) {
            this.products.open(cb);
        } else {
            this.menu.open(cb);
        }
    }
}
