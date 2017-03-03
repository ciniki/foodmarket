//
// The app for the Food Market
//
function ciniki_foodmarket_main() {
    this.pricePercentToggles = {'0.00':'0%', '0.10':'10%', '0.20':'20%', '0.25':'25%', '0.30':'30%', '0.40':'40%', '0.50':'50%', '0.75':'75%', '1.00':'100%'};
    this.priceSpecialsPercentToggles = {'0.00':'0%', '0.05':'5%', '0.10':'10%', '0.15':'15%', '0.20':'20%', '0.25':'25%', '0.30':'30%', '0.40':'40%', '0.50':'50%'};
    this.weightFlags = {'2':{'name':'lb'}, '3':{'name':'oz'}, '6':{'name':'kg'}, '7':{'name':'g'}};
    this.unitFlags = {'9':{'name':'Each'}, '10':{'name':'Pair'}, '11':{'name':'Bunch'}, '12':{'name':'Bag'}};
    this.caseFlags = {'17':{'name':'Case'}, '18':{'name':'Bushel'}, };

    //
    // Food Market
    //
    this.menu = new M.panel('Food Market', 'ciniki_foodmarket_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.menu');
    this.menu.date_id = 0;
    this.menu.category_id = '';
    this.menu.packing_basket_id = 0;
    this.menu.customer_id = 0;
    this.menu.order_id = 0;
    this.menu.supplier_id = 0;
    this.menu.nplist = [];
    this.menu.nplists = {'orderitems':[]};
    this.menu.data = {};
    this.menu.sections = {
        '_tabs':{'label':'', 'type':'menutabs', 'selected':'checkout', 'tabs':{
            'checkout':{'label':'Checkout', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"checkout");'},
            'packing':{'label':'Packing', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"packing");'},
            'procurement':{'label':'Procurement', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"procurement");'},
            'baskets':{'label':'Baskets', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"baskets");'},
            'availability':{'label':'Availability', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"availability");'},
//            'inventory':{'label':'Inventory', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"inventory");'},
            'dates':{'label':'Dates', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"dates");'},
            'repeats':{'label':'Standing', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"repeats");'},
//            'queue':{'label':'Queue', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"queue");'},
            'favourites':{'label':'Favourites', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"favourites");'},
//            'specials':{'label':'Specials', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"specials");'},
            'products':{'label':'Products', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"products");'},
            'suppliers':{'label':'Suppliers', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"suppliers");'},
            'notes':{'label':'Notes', 'fn':'M.ciniki_foodmarket_main.menu.open(null,"notes");'},
//                'tools':{'label':'Tools', 'fn':'M.ciniki_foodmarket_main.menu.switchProductTab("tools");'},
//            'mail':{'label':'Mail', 'fn':''}, // This shows the notifications sent, setup mail system to allow mail mailing list, or order date(s) customers.
            }},
        '_product_tabs':{'label':'', 'type':'menutabs', 'selected':'categories', 
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'products') ? 'yes':'no'; },
            'tabs':{
                'categories':{'label':'Categories', 'fn':'M.ciniki_foodmarket_main.menu.switchProductTab("categories");'},
//                'suppliers':{'label':'Suppliers', 'fn':'M.ciniki_foodmarket_main.menu.switchProductTab("suppliers");'},
                'specials':{'label':'Specials', 'fn':'M.ciniki_foodmarket_main.menu.switchProductTab("specials");'},
                'new':{'label':'New', 'fn':'M.ciniki_foodmarket_main.menu.switchProductTab("new");'},
//                'packing':{'label':'Packing', 'fn':'M.ciniki_foodmarket_main.menu.switchProductTab("packing");'},
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
        /* Common Elements */
        '_dates':{'label':'Change Date', 'aside':'yes',
            'visible':function() { 
                var t=M.ciniki_foodmarket_main.menu.sections._tabs.selected; 
                return (t == 'checkout' || t == 'packing' || t == 'procurement' || t == 'availability' || t == 'baskets') ? 'yes':'no'; 
                },
            'fields':{
                'date_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'onchangeFn':'M.ciniki_foodmarket_main.menu.switchDate', 
                    'complex_options':{'name':'name_status', 'value':'id'}, 'options':{},
                    },
            }},
        'customer_details':{'label':'Customer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { 
                var t=M.ciniki_foodmarket_main.menu.sections._tabs.selected; 
                return (t == 'checkout' && M.ciniki_foodmarket_main.menu.data.customer_details != null ) ? 'yes':'no'; 
                },
            'cellClasses':['label',''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_foodmarket_main.menu.open();\',\'mc\',{\'customer_id\':M.ciniki_foodmarket_main.menu.customer_id});',
            },
        'customers':{'label':'Customers', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { 
                var t=M.ciniki_foodmarket_main.menu.sections._tabs.selected; 
                return (t=='favourites' || t == 'repeats' || t == 'notes' ) ? 'yes':'no'; 
                },
            'noData':'No customers.',
            'addTxt':'',
            'addFn':'',
            },
        /* Checkout */
        'checkout_open_orders':{'label':'Open Orders', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout') ? 'yes':'no'; },
            'noData':'No open orders',
            'addTxt':'Add',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_foodmarket_main.menu.open();\',\'mc\',{\'next\':\'M.ciniki_foodmarket_main.menu.newOrder\',\'customer_id\':0});',
            },
        'checkout_closed_orders':{'label':'Closed Orders', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout') ? 'yes':'no'; },
            'noData':'No closed orders',
            },
        '_checkouttabs':{'label':'&nbsp;', 'type':'paneltabs', 'selected':'order', 
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'tabs':{
                'order':{'label':'Order', 'fn':'M.ciniki_foodmarket_main.menu.switchCheckoutTab("order");'},
                'recentledger':{'label':'Account', 'fn':'M.ciniki_foodmarket_main.menu.switchCheckoutTab("recentledger");'},
            }},
        'checkout_itemsearch':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 'hint':'Search',
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'order' 
                && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'cellClasses':['', 'nobreak', 'alignright nobreak'],
            'noData':'No products found',
            },
        'checkout_orderitems':{'label':'Items', 'type':'simplegrid', 'num_cols':6,
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'order' 
                && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'headerValues':['', 'Item', 'Quantity', 'Weight', 'Price', 'Total'],
            'headerClasses':['', '', 'alignright', 'aligncenter', 'alignright', 'alignright'],
            'cellClasses':['alignright', 'multiline', 'multiline alignright nobreak', 'aligncenter', 'multiline nobreak', 'multiline alignright nobreak'],
            'addTxt':'Add',
            'addFn':'M.ciniki_foodmarket_main.orderitem.open(\'M.ciniki_foodmarket_main.menu.open();\',0,M.ciniki_foodmarket_main.menu.order_id,[]);',
            },
        'checkout_tallies':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'order' 
                && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'cellClasses':['alignright', 'alignright'],
            },
        'checkout_payments':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'order' 
                && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'cellClasses':['alignright', 'alignright'],
            },
        'checkout_account':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'order' 
                && M.ciniki_foodmarket_main.menu.data.checkout_account != null ) ? 'yes':'no'; },
            'cellClasses':['alignright', 'alignright'],
            },
        'checkout_ordermessages':{'label':'Messages', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'order' 
                && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'cellClasses':['multiline', 'multiline'],
            'addTxt':'Email Customer',
            'addFn':'M.ciniki_foodmarket_main.email.open(\'M.ciniki_foodmarket_main.menu.open();\',M.ciniki_foodmarket_main.menu.order_id);',
            },
        'checkout_recentledger':{'label':'Last 15 transactions', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'recentledger' 
                && M.ciniki_foodmarket_main.menu.customer_id > 0 ) ? 'yes':'no'; },
            'headerValues':['Date', 'Transaction', 'Amount', 'Balance'],
            },
        'checkout_orderbuttons':{'label':'', 
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'order' 
                && M.ciniki_foodmarket_main.menu.data.order != null
                && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'buttons':{
                'createinvoice':{'label':'Invoice Customer', 
                    'visible':function() {return (M.ciniki_foodmarket_main.menu.data.order.payment_status==0 && M.ciniki_foodmarket_main.menu.data.order.items.length > 0 ? 'yes':'no');},
                    'fn':'M.ciniki_foodmarket_main.menu.invoiceCreate();'},
                'addcredit':{'label':'Add Credit', 
                    'visible':function() {return (M.ciniki_foodmarket_main.menu.data.order.payment_status > 0 
                        && M.ciniki_foodmarket_main.menu.data.order.default_payment_amount > 0 ?'yes':'no');},
                    'fn':'M.ciniki_foodmarket_main.ledgerentry.open(\'M.ciniki_foodmarket_main.menu.addCredit();\',0,10,M.ciniki_foodmarket_main.menu.data.order.default_payment_amount,M.ciniki_foodmarket_main.menu.customer_id);'},
                'addpayment':{'label':'Add Payment', 
                    'visible':function() {return (M.ciniki_foodmarket_main.menu.data.order.payment_status > 0 
                        && M.ciniki_foodmarket_main.menu.data.order.default_payment_amount > 0 ?'yes':'no');},
                    'fn':'M.ciniki_foodmarket_main.ledgerentry.open(\'M.ciniki_foodmarket_main.menu.addPayment();\',0,60,M.ciniki_foodmarket_main.menu.data.order.default_payment_amount,M.ciniki_foodmarket_main.menu.customer_id);'},
                'closeorder':{'label':'Close Order', 
                    'visible':function() {return (M.ciniki_foodmarket_main.menu.data.order.payment_status > 0 && M.ciniki_foodmarket_main.menu.data.order.items.length > 0 ?'yes':'no');},
                    'fn':'M.ciniki_foodmarket_main.menu.closeOrder();'},
                'delete':{'label':'Delete Order', 
                    'visible':function() {return (M.ciniki_foodmarket_main.menu.data.order.payment_status == 0 && M.ciniki_foodmarket_main.menu.data.order.items.length == 0 ?'yes':'no');},
                    'fn':'M.ciniki_foodmarket_main.menu.deleteOrder();'},
                'downloadpdf':{'label':'Print Invoice', 
//                    'visible':function() {return (M.ciniki_foodmarket_main.menu.data.order.payment_status > 0 ?'yes':'no');},
                    'fn':'M.ciniki_foodmarket_main.menu.printOrder();'},
            }},
        'checkout_accountbuttons':{'label':'', 
            'visible':function() { return ( M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'checkout' 
                && M.ciniki_foodmarket_main.menu.sections._checkouttabs.selected == 'recentledger' 
                && M.ciniki_foodmarket_main.menu.data.order != null
                && M.ciniki_foodmarket_main.menu.customer_id > 0 ) ? 'yes':'no'; },
            'buttons':{
                'addcredit':{'label':'Add Credit', 
                    'fn':'M.ciniki_foodmarket_main.ledgerentry.open(\'M.ciniki_foodmarket_main.menu.addCredit();\',0,10,M.ciniki_foodmarket_main.menu.data.order.default_payment_amount,M.ciniki_foodmarket_main.menu.customer_id);'},
                'addpayment':{'label':'Add Payment', 
                    'fn':'M.ciniki_foodmarket_main.ledgerentry.open(\'M.ciniki_foodmarket_main.menu.addPayment();\',0,60,M.ciniki_foodmarket_main.menu.data.order.balance_amount,M.ciniki_foodmarket_main.menu.customer_id);'},
            }},

        /* Packing */
        'packing_tabs':{'label':'', 'type':'paneltabs', 'selected':'orders', 'aside':'yes', 
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing') ? 'yes':'no'; },
            'tabs':{
                'orders':{'label':'Orders', 'fn':'M.ciniki_foodmarket_main.menu.packingSwitchTab("orders");'},
                'baskets':{'label':'Baskets', 'fn':'M.ciniki_foodmarket_main.menu.packingSwitchTab("baskets");'},
            }},
        'unpacked_orders':{'label':'Unpacked', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { 
                return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing' && M.ciniki_foodmarket_main.menu.sections.packing_tabs.selected == 'orders') ? 'yes':'no'; 
                },
            'noData':'No open orders',
            },
        'packing_buttons':{'label':'', 'aside':'yes',
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing' && M.ciniki_foodmarket_main.menu.sections.packing_tabs.selected == 'orders' ) ? 'yes':'no'; },
            'buttons':{
                'pack':{'label':'Print Packing Lists', 'fn':'M.ciniki_foodmarket_main.menu.packingPrintDate();'},
            }},
        'packed_orders':{'label':'Packed', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing' && M.ciniki_foodmarket_main.menu.sections.packing_tabs.selected == 'orders') ? 'yes':'no'; },
            'noData':'No closed orders',
            },
        'packing_orderitems':{'label':'Items', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing' && M.ciniki_foodmarket_main.menu.sections.packing_tabs.selected == 'orders' && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'headerValues':['Item', 'Quantity'],
            'headerClasses':['', 'alignright'],
            // Last column should be remove/adjust button
            'cellClasses':['', 'alignright'],
            },
        'packing_baskets':{'label':'Basket Orders', 'type':'simplegrid', 'num_cols':2, 'aside':'yes', 
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing' && M.ciniki_foodmarket_main.menu.sections.packing_tabs.selected == 'baskets' ) ? 'yes':'no'; },
            'cellClasses':['multiline', 'alignright'],
            'noData':'No Baskets',
            },
        'packing_basket_items':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing' && M.ciniki_foodmarket_main.menu.sections.packing_tabs.selected == 'baskets' && M.ciniki_foodmarket_main.menu.packing_basket_id > 0 ) ? 'yes':'no'; },
            'headerValues':['Item', 'Price', 'Quantity'],
            'headerClasses':['', 'alignright', 'alignright'],
            'cellClasses':['', 'alignright', 'multiline alignright'],
            'footerClasses':['', 'alignright', 'alignright'],
            },
        'packing_basket_outputs':{'label':'Available Items', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing' && M.ciniki_foodmarket_main.menu.sections.packing_tabs.selected == 'baskets' && M.ciniki_foodmarket_main.menu.packing_basket_id > 0 ) ? 'yes':'no'; },
            'headerValues':['Item', ''],
            'cellClasses':['', 'multiline alignright'],
            },
        'packing_order_buttons':{'label':'', 
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'packing' && M.ciniki_foodmarket_main.menu.sections.packing_tabs.selected == 'orders' && M.ciniki_foodmarket_main.menu.order_id > 0 ) ? 'yes':'no'; },
            'buttons':{
                'pack':{'label':'Order Packed', 'fn':'M.ciniki_foodmarket_main.menu.packingOrderPacked();'},
            }},

        /* Procurement */
        'procurement_suppliers':{'label':'Suppliers', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'procurement' ) ? 'yes':'no'; },
            'noData':'Nothing ordered',
            },
        'procurement_supplier_inputs':{'label':'Requested Items', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'procurement' && M.ciniki_foodmarket_main.menu.supplier_id > 0) ? 'yes':'no'; },
            'noData':'Nothing ordered',
            'headerValues':['SKU', 'Product', 'Cost', 'Required', 'Order'],
            'cellClasses':['', '', 'nobreak', 'nobreak', 'nobreak'],
            },
        'procurement_supplier_order':{'label':'Order', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'procurement' && M.ciniki_foodmarket_main.menu.supplier_id > 0) ? 'yes':'no'; },
            'noData':'Nothing ordered',
            'headerValues':['Code', 'Description', 'Quantity', 'Case/Single'],
            'headerClasses':['', '', 'aligncenter nobreak', 'aligncenter nobreak'],
            'cellClasses':['', '', 'aligncenter nobreak', 'aligncenter nobreak'],
            },

        /* Baskets */
        'baskets_items':{'label':'Baskets', 'type':'simplegrid', 'num_cols':5,
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='baskets'?'yes':'no';},
            'sortable':'yes',
            'sortTypes':['text', 'text', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber'],
            'headerValues':[],
            'basket_ids':[],
            },
        'baskets_buttons':{'label':'', 
            'visible':function() {
                return (M.ciniki_foodmarket_main.menu.sections._tabs.selected=='baskets' && M.ciniki_foodmarket_main.menu.data.date_status < 30) ? 'yes':'no';
                },
            'buttons':{
                'substitutions':{'label':'Enable Substitutions', 'fn':'M.ciniki_foodmarket_main.menu.basketsSubmit();'},
            }},
        'baskets_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':4, 'hint':'Search',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='baskets'?'yes':'no';},
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright multiline'],
            'noData':'No products found',
            },
        'baskets_recent_outputs':{'label':'Recent Basket Products', 'type':'simplegrid', 'num_cols':4,
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='baskets'?'yes':'no';},
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright multiline'],
            'noData':'No recent products found',
            },
        'baskets_outputs':{'label':'Basket Products', 'type':'simplegrid', 'num_cols':4,
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='baskets'?'yes':'no';},
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright multiline'],
            'noData':'No basket products found',
            },
        /* Availability */
        'availability_date_outputs':{'label':'Available Products', 'aside':'yes', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'availability') ? 'yes':'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', ''],
            'headerValues':['Supplier', 'Product', ''],
            'cellClasses':['', '', 'alignright multiline'],
            'noData':'No products available for this date',
            },
        'availability_date_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':4, 'hint':'Search',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='availability'?'yes':'no';},
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright'],
            'noData':'No products found',
            },
        'availability_recent_outputs':{'label':'Recent', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'availability') ? 'yes':'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'date', ''],
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright multiline'],
            'noData':'No recent date limited products',
            },
        'availability_outputs':{'label':'Dated Products', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'availability') ? 'yes':'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'date', ''],
            'headerValues':['Supplier', 'Product', 'Last Available', ''],
            'cellClasses':['', '', '', 'alignright multiline'],
            'noData':'No date limited products',
            },
        /* Inventory */

        /* Dates */
        'order_dates':{'label':'Order Dates', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'dates') ? 'yes':'no'; },
            'headerValues':['Status', 'Date', '# Orders'],
            'noData':'No order dates have been setup.',
            'addTxt':'Add Order Date',
            'addFn':'M.ciniki_foodmarket_main.editdate.open(\'M.ciniki_foodmarket_main.menu.open();\',0,null);'
            },

        /* Repeats */
        'repeat_items':{'label':'Standing Order Items', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'repeats' && M.ciniki_foodmarket_main.menu.customer_id == 0 ) ? 'yes':'no'; },
            'headerValues':['Item', '# Customers'],
            'noData':'No standing order items',
            },
        'customer_repeats':{'label':'Standing Items', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return (M.ciniki_foodmarket_main.menu.sections._tabs.selected == 'repeats' && M.ciniki_foodmarket_main.menu.customer_id > 0 ) ? 'yes':'no'; },
            'headerValues':['Item', 'Qty', 'Last', 'Next', '# Orders'],
            'sortable':'yes', 'sortTypes':['text', 'number', 'date', 'date', 'number'],
            'noData':'No standing orders for customer',
            'cellClasses':['', '', 'nobreak', 'nobreak', ''],
            'addTxt':'Add',
            'addFn':'M.ciniki_foodmarket_main.repeatitem.open(\'M.ciniki_foodmarket_main.menu.open();\',\'0\',M.ciniki_foodmarket_main.menu.customer_id);'
            },

        /* Queue */

        /* Favourites */
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

        /* Products - Categories */
        'product_categories':{'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() {var p=M.ciniki_foodmarket_main.menu; return p.sections._tabs.selected=='products' && p.sections._product_tabs.selected=='categories'?'yes':'no';},
            'cellClasses':['', 'alignright'],
            'addTxt':'Add Category',
            'addFn':'M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.menu.open();\',0);',
            },
        'product_tools':{'label':'Tools', 'aside':'yes',
            'visible':function() {var p=M.ciniki_foodmarket_main.menu; return p.sections._tabs.selected=='products' && p.sections._product_tabs.selected=='categories'?'yes':'no';},
            'list':{
                'printcatalog':{'label':'Print Catalog', 'fn':'M.ciniki_foodmarket_main.printcatalog.open(\'M.ciniki_foodmarket_main.menu.open();\');'},
                }},
        'product_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 
            'visible':function() {var p=M.ciniki_foodmarket_main.menu; return p.sections._tabs.selected=='products' && p.sections._product_tabs.selected=='categories'?'yes':'no';},
            'headerValues':['Supplier', 'Name', 'Status'],
            'cellClasses':[''],
            'hint':'Search products', 
            'noData':'No products found',
            },
        'products':{'label':'Products', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
            'visible':function() {var p=M.ciniki_foodmarket_main.menu; return p.sections._tabs.selected=='products' && p.sections._product_tabs.selected=='categories'?'yes':'no';},
            'headerValues':['Supplier', 'Name', 'Types'],
            'cellClasses':['', '', ''],
            'sortTypes':['text', 'text', 'text'],
            'noData':'No Products',
            'addTxt':'Add Product',
            'addFn':'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',0,null,null,M.ciniki_foodmarket_main.menu.category_id);',
            },
/*        'productinputs':{'label':'Inventory', 'type':'simplegrid', 'num_cols':4, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='inventory'?'yes':'no';},
            'headerValues':['Category', 'Product', 'Option', 'Inventory'],
            'cellClasses':['', '', '', ''],
            'sortTypes':['text', 'text', 'text', 'number'],
            'noData':'No Products',
            }, */

        /* Products - Specials */
        'specials_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':4, 
            'visible':function() {var p=M.ciniki_foodmarket_main.menu; return (p.sections._tabs.selected=='products' && p.sections._product_tabs.selected=='specials')?'yes':'no';},
            'headerValues':['Supplier', 'Name', '', 'Price'],
            'cellClasses':[''],
            'hint':'Search products', 
            'noData':'No products found',
            },
        'specials_outputs':{'label':'Specials', 'type':'simplegrid', 'num_cols':4, 'sortable':'yes', 
            'fields':{
                'retail_sdiscount_percent':{'label':'', 'type':'toggle', 'toggles':this.priceSpecialsPercentToggles, 'onchange':'M.ciniki_foodmarket_main.menu.specialsUpdate'}, 
            },
            'visible':function() {var p=M.ciniki_foodmarket_main.menu; return (p.sections._tabs.selected=='products' && p.sections._product_tabs.selected=='specials')?'yes':'no';},
            'headerValues':['Supplier', 'Name', '', 'Price'],
            'cellClasses':['', '', 'nobreak multiline', 'nobreak'],
            'sortTypes':['text', 'text', 'text', 'text'],
            'noData':'No Products',
            'addTxt':'Add Product',
            'addFn':'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',0);',
            },
        /* Products - Specials */
        'new_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 
            'visible':function() {var p=M.ciniki_foodmarket_main.menu; return (p.sections._tabs.selected=='products' && p.sections._product_tabs.selected=='new')?'yes':'no';},
            'headerValues':['Supplier', 'Name', ''],
            'cellClasses':[''],
            'hint':'Search products', 
            'noData':'No products found',
            },
        'new_products':{'label':'Specials', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes', 
            'visible':function() {var p=M.ciniki_foodmarket_main.menu; return (p.sections._tabs.selected=='products' && p.sections._product_tabs.selected=='new')?'yes':'no';},
            'headerValues':['Supplier', 'Name', ''],
            'cellClasses':['', '', 'multiline'],
            'sortTypes':['text', 'text', 'text'],
            'noData':'No Products',
            },

        /* Suppliers */
        'suppliers':{'label':'Suppliers', 'type':'simplegrid', 'num_cols':3, 'sortable':'yes',
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='suppliers'?'yes':'no';},
            'cellClasses':['', ''],
            'headerValues':['Code', 'Supplier', '# of Products'],
            'sortTypes':['text', 'text', 'number'],
            'noData':'No supplier',
            'addTxt':'Add Supplier',
            'addFn':'M.ciniki_foodmarket_main.supplier.open(\'M.ciniki_foodmarket_main.menu.open();\',0);',
            },

        /* Notes */
        'notes':{'label':'Notes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='notes'?'yes':'no';},
            'noData':'No note',
            'cellClasses':['multiline', 'multiline'],
            'addTxt':'Add Note',
            'addFn':'M.ciniki_foodmarket_main.note.open(\'M.ciniki_foodmarket_main.menu.open();\',0,M.ciniki_foodmarket_main.menu.customer_id, null);'
            },
        'archived_notes':{'label':'Archived', 'type':'simplegrid', 'num_cols':2,
            'visible':function() {return M.ciniki_foodmarket_main.menu.sections._tabs.selected=='notes'?'yes':'no';},
            'noData':'No note',
            'cellClasses':['multiline', 'multiline'],
            },

    };
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.fieldValue = function(s, i, d) {
        if( s == 'specials_search' || s == 'specials_outputs' ) {
            return this.data[i];
            // return parseFloat(d.value);
        }
        return this.date_id;
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'date_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.dateItemSearch', {'business_id':M.curBusinessID, 'search_str':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.menu.liveSearchShow('date_search',null,M.gE(M.ciniki_foodmarket_main.menu.panelUID + '_' + s), rsp.products);
                });
        }
        if( s == 'baskets_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.dateBasketItemSearch', {'business_id':M.curBusinessID, 'search_str':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.menu.liveSearchShow('baskets_search',null,M.gE(M.ciniki_foodmarket_main.menu.panelUID + '_' + s), rsp.products);
                });
        }
        if( s == 'product_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.productSearch', {'business_id':M.curBusinessID, 'search_str':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.menu.liveSearchShow('product_search',null,M.gE(M.ciniki_foodmarket_main.menu.panelUID + '_' + s), rsp.products);
                });
        }
        if( s == 'specials_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.specialsSearch', {'business_id':M.curBusinessID, 'search_str':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.menu.liveSearchShow('specials_search',null,M.gE(M.ciniki_foodmarket_main.menu.panelUID + '_' + s), rsp.outputs);
                });
        }
        if( s == 'new_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.foodmarket.newSearch', {'business_id':M.curBusinessID, 'search_str':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.menu.liveSearchShow('new_search',null,M.gE(M.ciniki_foodmarket_main.menu.panelUID + '_' + s), rsp.products);
                });
        }
        if( s == 'checkout_itemsearch' && v != '' ) {
            M.api.getJSONBgCb('ciniki.poma.orderItemSearch', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'50'}, function(rsp) {
                    M.ciniki_foodmarket_main.menu.liveSearchShow('checkout_itemsearch',null,M.gE(M.ciniki_foodmarket_main.menu.panelUID + '_' + s), rsp.items);
                });
        }
    }
    this.menu.liveSearchResultClass = function(s, f, i, j, d) {
        if( s == 'checkout_itemsearch' ) { 
            switch(j) {
                case 1: return 'nobreak multiline';
                case 2: return 'nobreak multiline';
            }   
            return '';
        }
        if( s == 'baskets_search' && j == 3 ) { 
            return 'alignright multiline';
        }
        return '';
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'checkout_itemsearch' ) { 
            switch(j) {
                case 0: return d.name;
                case 1: return '<span class="maintext">' + d.size + '</span><span class="subtext">' + d.unit_price_text + '</span>';
                case 2: return '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutItemAdd("' + d.object + '","' + d.object_id + '","1");return false;\'>1</button>'
                    + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutItemAdd("' + d.object + '","' + d.object_id + '","2");return false;\'>2</button>'
                    + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutItemAdd("' + d.object + '","' + d.object_id + '","3");return false;\'>3</button>'
                    + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutItemAdd("' + d.object + '","' + d.object_id + '","4");return false;\'>4</button>'
                    + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutItemAdd("' + d.object + '","' + d.object_id + '","5");return false;\'>5</button>'
                    + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutItemAdd("' + d.object + '","' + d.object_id + '","6");return false;\'>6</button>';
            }
        }
        if( s == 'date_search' ) { 
            return this.dateProductCellValue(s, i, j, d);
        }
        if( s == 'baskets_search' ) { 
            return this.dateBasketCellValue(s, i, j, d);
        }
        if( s == 'specials_search' ) { 
            return this.specialsCell(s, i, j, d);
        }
        if( s == 'new_search' ) { 
            switch(j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.newProductAdd("' + d.id + '");\'>Add</button>';
            }
        }
        if( s == 'product_search' ) { 
            switch(j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return d.status_text;
            }
        }
    }
    this.menu.dateBasketCellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.supplier_code;
            case 1: return d.name;
            case 2: return d.last_order_date;
            case 3: return '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.basketItemAdd(event,"' + d.id + '");return false;\'>Add</button>';
        }
        return '';
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
    this.menu.specialsCell = function(s, i, j, d) {
        if( s == 'specials_search' && j == 2 ) {
            return ''
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.00");return false;\'>0%</button>'
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.05");return false;\'>5%</button>'
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.10");return false;\'>10%</button>'
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.15");return false;\'>15%</button>'
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.20");return false;\'>20%</button>'
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.25");return false;\'>25%</button>'
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.30");return false;\'>30%</button>'
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.40");return false;\'>40%</button>'
                + '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.specialsAdd("' + d.id + '","0.50");return false;\'>50%</button>'
                + '';
        }
        switch(j) {
            case 0: return d.supplier_code;
            case 1: return d.pio_name;
            case 2: 
                M.ciniki_foodmarket_main.menu.data[d.id] = parseFloat(d.retail_sdiscount_percent);
                var f = this.createFormField(s, d.id, this.sections.specials_outputs.fields.retail_sdiscount_percent, d.id); 
                return f.innerHTML;
            case 3: 
                if( d.retail_sprice_text != '' ) {
                    return '<s>' + d.retail_price_text + '</s> ' + d.retail_sprice_text;
                }
                return d.retail_price_text;
        }
        return '';
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'checkout_itemsearch' ) { 
            return 'M.ciniki_foodmarket_main.menu.checkoutItemAdd("' + d.object + '","' + d.object_id + '","1");';
        }
        if( s == 'date_search' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\');';
        }
        if( s == 'baskets_search' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\');';
        }
        if( s == 'specials_search' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\');';
        }
        if( s == 'new_search' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\');';
        } 
        if( s == 'product_search' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\');';
        }
        return '';
    }
    this.menu.rowClass = function(s, i, d) {
        if( (s == 'checkout_open_orders' || s == 'checkout_closed_orders' || s == 'unpacked_orders' || s == 'packed_orders' ) && this.order_id == d.id ) {
            return 'highlight';
        }
        if( s == 'customers' && this.customer_id == d.id ) {
            return 'highlight';
        }
        if( s == 'packing_baskets' && this.packing_basket_id == d.order_basket_id ) {
            return 'highlight';
        }
        if( s == 'checkout_payments' && d.status != null ) {
            return 'status' + d.status;
        }
        if( s == 'checkout_account' && d.status != null ) {
            return 'status' + d.status;
        }
        if( s == 'specials_search' || s == 'specials_outputs' ) {   
            return 'textfield toggle';
        }
        return '';
    }
    this.menu.cellValue = function(s, i, j, d) {
        /* Common elements */
        if( s == 'customer_details' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
            }
        }
        /* Checkout */
        if( s == 'checkout_open_orders' || s == 'checkout_closed_orders' ) { 
            return (d.num_notes != null && d.num_notes > 0 ? '*' : '')
                + d.billing_name 
                + (d.payment_status != null && d.payment_status != '' ? ' <span class="subdue">[' + d.payment_status + ']</span>' : '');
        }
        if( s == 'checkout_orderitems' ) {
            switch(j) {
                case 0: return '<span class="subdue">' + (parseInt(i) + 1) + '</span>';
                case 1: 
                    if( d.notes != '' ) {
                        return '<span class="maintext">' + d.description + '</span><span class="subtext">' + d.notes + '</span>';
                    }
                    return d.description;
                case 2:
                    if( d.itype == '10' ) { return ''; }
                    var bid = d.id;
                    var q = parseFloat(d.quantity);
                    return '<span class="pmbutton"><span class="pm-down" onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutUnitQuantityUpdate(event,"' + d.id + '","' + (q-1) + '");return false;\'>-</span>'
                        + '<span class="pm-value" onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutUnitQuantityGet(event,"' + d.id + '");return false;\'>' + q + '</span>'
                        + '<span class="pm-up" onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.checkoutUnitQuantityUpdate(event,"' + d.id + '","' + (q+1) + '");return false;\'>+</span>'
                        + '</span>';
                case 3: 
                    if( d.itype != '10' && d.itype != '20' ) { return ''; }
                    var q = parseFloat(d.weight_quantity);
                    if( d.itype == '20' && q == 0 ) { return 'TBD'; }
                    return parseFloat(d.weight_quantity);
                case 4:
                    if( d.discount_text != '' && d.deposit_text != '' ) {
                        return '<span class="maintext">@ ' + d.unit_price_text + '</span>'
                            + '<span class="subtext">' + d.discount_text + '</span>'
                            + '<span class="subtext">' + d.deposit_text + '</span>';
                    } else if( d.deposit_text != '' ) {
                        return '<span class="maintext">@ ' + d.unit_price_text + '</span><span class="subtext">' + d.deposit_text + '</span>';
                    } else if( d.discount_text != '' ) {
                        return '<span class="maintext">@ ' + d.unit_price_text + '</span><span class="subtext">' + d.discount_text + '</span>';
                    }
                    return '@ ' + d.unit_price_text;
                case 5: 
                    if( d.taxtype_name != null && d.taxtype_name != '' ) {
                        return '<span class="maintext">' + d.total_text + '</span><span class="subtext">' + d.taxtype_name + '</span>';
                    }
                    return d.total_text;
            }
        }
        if( s == 'checkout_tallies' || s == 'checkout_payments' || s == 'checkout_account' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'checkout_ordermessages' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.message.status_text + '</span><span class="subtext">' + d.message.date_sent + '</span>';
                case 1: return '<span class="maintext">' + d.message.customer_email + '</span><span class="subtext">' + d.message.subject + '</span>';
            }
        }
        if( s == 'checkout_recentledger' ) {
            switch(j) {
                case 0: return d.transaction_date;
                case 1: return d.description;
                case 2: return d.amount;
                case 3: return d.balance_text;
            }
        }

        /* Packing */
        if( s == 'unpacked_orders' ) {
            return d.billing_name;
        }
        if( s == 'packed_orders' ) {
            return d.billing_name;
        }
        if( s == 'packing_orderitems' ) {
            switch(j) {
                case 0: return d.description;
                case 1: return d.quantity;
            }
        }
        if( s == 'packing_baskets' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.billing_name + '</span><span class="subtext">' + d.basket_name + '</span>';
                case 1: return d.total_amount + ' (' + d.total_percent + ')';
            }
        }
        if( s == 'packing_basket_items' ) {
            if( j == 2 ) {
                q = parseFloat(d.quantity);
                return '<span class="pmbutton">'
                    + '<span class="pm-down" onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.packingBasketUpdateQty("' + d.id + '","' + (q-1) + '");return false;\'>-</span>'
                    + '<span class="pm-value">' + q + '</span>'
                    + '<span class="pm-up" onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.packingBasketUpdateQty("' + d.id + '","' + (q+1) + '");return false;\'>+</span>'
                    + '</span>';
            }
            switch(j) {
                case 0: return d.description;
                case 1: return d.price_text;
            }
        }
        if( s == 'packing_basket_outputs' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.packingBasketItemAdd("' + d.id + '");return false;\'>Add</button>';
            }
        }

        /* Procurement */
        if( s == 'procurement_suppliers' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
        if( s == 'procurement_supplier_inputs' ) {
            switch(j) {
                case 0: return d.sku;
                case 1: return d.name;
                case 2: return d.cost_text;
                case 3: return d.required_quantity_text;
                case 4: return d.order_quantity_text;
            }
        }
        if( s == 'procurement_supplier_order' ) {
            switch(j) {
                case 0: return d.sku;
                case 1: return d.name;
                case 2: return d.quantity;
                case 3: return d.size;
            }
        }

        /* Baskets */
        if( s == 'baskets_items' ) {
            switch(j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return d.price_text;
            }
            if( j == (this.sections[s].num_cols-3) ) {
                return d.quantity_text;
            }
            if( j == (this.sections[s].num_cols-2) ) {
                return d.percent_text;
            }
            if( j == (this.sections[s].num_cols-1) ) {
                if( d.quantity <= 0 ) {
                    return '<button onclick=\'event.stopPropagation();M.ciniki_foodmarket_main.menu.basketItemRemove(event,"' + d.id + '");\'>Remove</button>';
                }
                return '';
            }
            var bid = this.sections[s].basket_ids[(j-3)];
            var q = '';
            if( d.basket_quantities != null && d.basket_quantities[bid] != null ) {
                q = parseFloat(d.basket_quantities[bid].quantity);
            } 
            return '<span class="pmbutton"><span class="pm-down" onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.bqUpdate(event,"' + bid + '","' + d.id + '","' + (q-1) + '");return false;\'>-</span>'
                + '<span class="pm-value">' + q + '</span>'
                + '<span class="pm-up" onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.bqUpdate(event,"' + bid + '","' + d.id + '","' + (q+1) + '");return false;\'>+</span>'
                + '</span>';
        }
        if( s == 'baskets_recent_outputs' || s == 'baskets_outputs' ) {
            return this.dateBasketCellValue(s, i, j, d);
        }

        /* Availability */
        if( s == 'availability_date_outputs' ) {
            switch(j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.dateItemDelete(event,"' + d.id + '");return false;\'>Delete</button>';
            }
        }
        if( s == 'availability_recent_outputs' || s == 'availability_outputs' ) {
            return this.dateProductCellValue(s, i, j, d);
        }

        /* Dates */
        if( s == 'order_dates' ) {
            switch(j) {
                case 0: return d.status_text;
                case 1: return d.display_name;
                case 2: return d.num_orders;
            }
        }

        /* Repeats */
        if( s == 'repeat_items' ) {
            switch(j) {
                case 0: return d.description;
                case 1: return d.num_customers;
            }
        } else if( s == 'customer_repeats' ) {
            switch(j) {
                case 0: return d.description;
                case 1: return d.quantity;
                case 2: return d.last_order_date;
                case 3: return d.next_order_date;
                case 4: return d.num_orders;
            }
        }

        /* Queue */ 

        /* Favourites */
        if( s == 'favourite_items' ) {
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
        /* Specials */
        if( s == 'specials_outputs' ) {
            return this.specialsCell(s, i, j, d);
        }

        /* Products */
        if( s == 'product_categories' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return (d.num_products != null && d.num_products > 0 ? ' <span class="count">' + d.num_products + '</span>' : '');
            }
        } 
        if( s == 'products' || s == 'supplier_products' ) {
            switch (j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return d.input_names;
            }
        } else if( s == 'customers' ) {
            return (d.num_notes != null && d.num_notes > 0 ? '*' : '') 
                + d.display_name 
                + (d.num_items != null && d.num_items > 0 ? ' <span class="count">' + d.num_items + '</span>' : '');
        } 
        if( s == 'new_products' ) { 
            switch(j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return '<button onclick=\'event.stopPropagation(); M.ciniki_foodmarket_main.menu.newProductRemove("' + d.id + '");\'>Remove</button>';
            }
        }
        /* Suppliers */
        if( s == 'suppliers' ) {
            switch(j) {
                case 0: return d.code;
                case 1: return d.name;
                case 2: return d.num_products;
            }
        } 
        /* Notes */
        if( s == 'notes' || s == 'archived_notes' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.note_date_text + '</span><span class="subtext">' + d.status_text + '</span>';
                case 1: return d.content;
            }
        } 
    };
    this.menu.cellSortValue = function(s, i, j, d) {
        if( s == 'baskets_items' ) {
            switch(j) {
                case 0: return d.supplier_code;
                case 1: return d.name;
                case 2: return d.price;
            }
            if( j == (this.sections[s].num_cols-2) ) {
                return d.quantity;
            }
            if( j == (this.sections[s].num_cols-1) ) {
                return d.percent;
            }
            var bid = this.sections[s].basket_ids[(j-3)];
            if( d.basket_quantities != null && d.basket_quantities[bid] != null ) {
                return d.basket_quantities[bid].quantity;
            } 
        }
        return '';
    }
    this.menu.cellFn = function(s, i, j, d) {
        if( s == 'procurement_supplier_inputs' && j == 2 ) {
            return 'return M.ciniki_foodmarket_main.menu.procurementUpdateCost(event,\'' + d.id + '\',\'' + d.itype + '\',"' + encodeURIComponent(d.name) + '",\'' + d.cost_suffix + '\');';
        }
        if( s == 'checkout_orderitems' && j == 3 && parseInt(d.itype) < 30 ) {
            return 'return M.ciniki_foodmarket_main.menu.checkoutWeightQuantityGet(event,"' + d.id + '");';
        }
        return null;
    }
    this.menu.rowFn = function(s, i, d) {
        /* Checkout */
        if( s == 'checkout_open_orders' || s == 'checkout_closed_orders' ) {
            return 'M.ciniki_foodmarket_main.menu.openOrder(\'' + d.id + '\');';
        } else if( s == 'checkout_orderitems' ) {
            return 'M.ciniki_foodmarket_main.orderitem.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\',null,M.ciniki_foodmarket_main.menu.nplists.orderitems);';
        }
        /* Packing */
        if( s == 'unpacked_orders' || s == 'packed_orders' ) {
            return 'M.ciniki_foodmarket_main.menu.packingOrderOpen(\'' + d.id + '\');';
        }
        if( s == 'packing_baskets' ) {
            return 'M.ciniki_foodmarket_main.menu.packingBasketOpen(\'' + d.order_basket_id + '\');';
        }
        /* Procurement */
        if( s == 'procurement_suppliers' ) {
            return 'M.ciniki_foodmarket_main.menu.openProcurementSupplier(\'' + d.id + '\');';
        }
        if( s == 'procurement_supplier_inputs' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\',null,M.ciniki_foodmarket_main.menu.nplist);';
        }
        /* Availability */
        if( s == 'baskets_items' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\');';
        } 
        /* Availability */
        if( s == 'availability_date_outputs' || s == 'availability_recent_outputs' || s == 'availability_outputs' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\');';
        } 
        /* Dates */
        if( s == 'order_dates' ) {
            return 'M.ciniki_foodmarket_main.editdate.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\',M.ciniki_foodmarket_main.menu.date_nplist);';
        }
        /* Repeats */
        if( s == 'customer_repeats' ) {
            return 'M.ciniki_foodmarket_main.repeatitem.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\',\'' + d.customer_id + '\');'
        }
        /* Favourites */
        if( s == 'customers' ) {
            return 'M.ciniki_foodmarket_main.menu.openFavourites(\'' + d.id + '\');';
        }
        /* Products */
        if( s == 'product_categories' ) {
            return 'M.ciniki_foodmarket_main.menu.openProducts(\'' + d.id + '\',\'' + escape(d.fullname) + '\');';
        } 
        /* Specials */
        if( s == 'specials_outputs' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.product_id + '\');';
        } 
        /* New */
        if( s == 'new_products' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\');';
        } 
        /* Suppliers */
        if( s == 'suppliers' ) {
            return 'M.ciniki_foodmarket_main.supplier.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\');';
        } 
        if( s == 'products' || s == 'supplier_products' ) {
            return 'M.ciniki_foodmarket_main.product.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\',null,M.ciniki_foodmarket_main.menu.nplist);';
        } 
        /* Notes */
        if( s == 'note_customers' ) {
            return 'M.ciniki_foodmarket_main.menu.openNotes(\'' + d.id + '\');';
        }
        if( s == 'notes' || s == 'archived_notes' ) {
            return 'M.ciniki_foodmarket_main.note.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + d.id + '\');';
        } 
        return '';
    };
    this.menu.footerValue = function(s, i, d) {
        if( s == 'baskets_items' ) {
            if( i > 2 && i < (this.sections[s].num_cols-3) ) {
                return this.data.baskets[(i-3)].total_text;
            }
            return '';
        }
        if( s == 'packing_basket_items' ) {
            if( i == 1 ) {
                return this.data.basket.curtotal_text + ' (' + this.data.basket.total_percent + ')';
            }
            return '';
        }
        return null;
    }

    /* Checkout */
    this.menu.newOrder = function(cid) {
        this.customer_id = cid;
        this.order_id = 0;
        M.api.getJSONCb('ciniki.poma.dateCheckout', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':0, 'order':'new', 'customer_id':this.customer_id}, 
            M.ciniki_foodmarket_main.menu.processCheckout);
    }
    this.menu.switchCheckoutTab = function(t) {
        this.sections._checkouttabs.selected = t;
        this.refresh();
        this.show();
    }
    this.menu.checkoutItemAdd = function(o,i,q) {
        M.api.getJSONCb('ciniki.poma.dateCheckout', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':this.order_id, 'new_object':o, 'new_object_id':i, 'new_quantity':q, 'customer_id':this.customer_id}, 
            M.ciniki_foodmarket_main.menu.processCheckout);
    }
    this.menu.checkoutUnitQuantityGet = function(e, i) {
        var q = prompt("Quantity: ", '');
        if( q != null && q != '' ) {
            this.checkoutUnitQuantityUpdate(e, i, q);
        }
    }
    this.menu.checkoutUnitQuantityUpdate = function(e,i,q) {
        M.api.getJSONCb('ciniki.poma.dateCheckout', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':this.order_id, 'item_id':i, 'new_unit_quantity':q, 'customer_id':this.customer_id}, 
            M.ciniki_foodmarket_main.menu.processCheckout);
    }
    this.menu.checkoutWeightQuantityGet = function(e, i) {
        var q = prompt("Weight: ", '');
        if( q != null && q != '' ) {
            M.api.getJSONCb('ciniki.poma.dateCheckout', 
                {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':this.order_id, 'item_id':i, 'new_weight_quantity':q, 'customer_id':this.customer_id}, 
                M.ciniki_foodmarket_main.menu.processCheckout);
        }
    }
    this.menu.invoiceCreate = function() {
        M.api.getJSONCb('ciniki.poma.dateCheckout', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':this.order_id, 'customer_id':this.customer_id, 'action':'invoiceorder'}, 
            M.ciniki_foodmarket_main.menu.processCheckout);
    }
    this.menu.addCredit = function() {
        M.api.getJSONCb('ciniki.poma.dateCheckout', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':this.order_id, 'customer_id':this.customer_id}, 
            M.ciniki_foodmarket_main.menu.processCheckout);
    }
    this.menu.addPayment = function() {
        M.api.getJSONCb('ciniki.poma.dateCheckout', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':this.order_id, 'customer_id':this.customer_id}, 
            M.ciniki_foodmarket_main.menu.processCheckout);
    }
    this.menu.closeOrder = function() {
        M.api.getJSONCb('ciniki.poma.dateCheckout', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':this.order_id, 'customer_id':this.customer_id, 'action':'closeorder'}, 
            M.ciniki_foodmarket_main.menu.processCheckout);
    }
    this.menu.deleteOrder = function() {
        M.api.getJSONCb('ciniki.poma.orderDelete', {'business_id':M.curBusinessID, 'order_id':this.order_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.menu;
            p.order_id = 0;
            p.customer_id = 0;
            p.open();
        });
    }
    this.menu.printOrder = function() {
        M.api.openPDF('ciniki.poma.invoicePDF', {'business_id':M.curBusinessID, 'order_id':this.order_id});
    }

    /* Packing */
    this.menu.packingSwitchTab = function(t) {
        this.sections.packing_tabs.selected = t;
        this.open();
    }
    this.menu.packingOrderOpen = function(i) {
        this.order_id = i;
        this.open();
    }
    this.menu.packingPrintDate = function() {
        M.api.openPDF('ciniki.foodmarket.datePackingLists', {'business_id':M.curBusinessID, 'date_id':this.date_id});
    }
    this.menu.packingOrderPacked = function() {
        M.api.getJSONCb('ciniki.foodmarket.datePacking', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'orders':'yes', 'order_id':this.order_id, 'order_packed':'yes'}, 
            M.ciniki_foodmarket_main.menu.processPacking);
    }
    this.menu.packingBasketOpen = function(i) {
        this.packing_basket_id = i;
        this.open();
    }
    this.menu.packingBasketUpdateQty = function(i, q) {
        M.api.getJSONCb('ciniki.foodmarket.datePacking', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'baskets':'yes', 'packing_basket_id':this.packing_basket_id, 
                'packing_basket_item_update':i, 'packing_basket_item_quantity':q}, 
            M.ciniki_foodmarket_main.menu.processPacking);
    }
    this.menu.packingBasketItemAdd = function(i) {
        M.api.getJSONCb('ciniki.foodmarket.datePacking', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'baskets':'yes', 'packing_basket_id':this.packing_basket_id, 
                'packing_basket_output_add':i}, 
            M.ciniki_foodmarket_main.menu.processPacking);
    }
    /* Procurement */
    this.menu.openProcurementSupplier = function(i) {
        this.supplier_id = i;
        this.open();
    }
    this.menu.procurementUpdateCost = function(e, i, t, n, s) {
        e.stopPropagation();
        var p = prompt('Enter new price for ' + decodeURIComponent(n));
        if( p != null && p != '' ) {
            p = parseFloat(p);
            if( t == 50 ) {
                M.api.getJSONCb('ciniki.foodmarket.inputUpdate', {'business_id':M.curBusinessID, 'input_id':i, 'case_cost':p}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    e.srcElement.innerHTML = '$' + p.toFixed(2) + s;
                });
            } else {
                M.api.getJSONCb('ciniki.foodmarket.inputUpdate', {'business_id':M.curBusinessID, 'input_id':i, 'unit_cost':p}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    e.srcElement.innerHTML = '$' + p.toFixed(2) + s;
                });
            }
        }
        return false;
    }

    /* Dates */
    this.menu.dateItemAdd = function(e, oid) {
        M.api.getJSONCb('ciniki.foodmarket.dateItems', {'business_id':M.curBusinessID, 'date_id':this.date_id, 'add_output_id':oid}, 
            M.ciniki_foodmarket_main.menu.processAvailability);
    }
    this.menu.dateItemDelete = function(e, oid) {
        M.api.getJSONCb('ciniki.foodmarket.dateItems', {'business_id':M.curBusinessID, 'date_id':this.date_id, 'delete_output_id':oid}, 
            M.ciniki_foodmarket_main.menu.processAvailability);
    }
    this.menu.basketItemRemove = function(e, iid) {
        M.api.getJSONCb('ciniki.foodmarket.dateBaskets', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'remove_item_id':iid, 'outputs':'yes'},
            M.ciniki_foodmarket_main.menu.processBaskets);
    }
    this.menu.basketItemAdd = function(e, oid) {
        M.api.getJSONCb('ciniki.foodmarket.dateBaskets', {'business_id':M.curBusinessID, 
            'date_id':this.date_id, 'basket_output_id':0, 'item_output_id':oid, 'quantity':0}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_foodmarket_main.menu;
                p.data.baskets_items = rsp.baskets_items;
                p.refreshSection('baskets_items');
                e.target.parentNode.parentNode.parentNode.removeChild(e.target.parentNode.parentNode);
            });
    }
    this.menu.bqUpdate = function(e, bid, oid, q) {
        if( q < 0 ) { q = 0; }
        M.api.getJSONCb('ciniki.foodmarket.dateBaskets', {'business_id':M.curBusinessID, 
            'date_id':this.date_id, 'basket_output_id':bid, 'item_output_id':oid, 'quantity':q}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_foodmarket_main.menu;
                p.data.baskets = rsp.baskets;
                p.data.baskets_items = rsp.baskets_items;
                p.refreshSection('baskets_items');
            });
    }
    this.menu.basketsSubmit = function() {
        M.api.getJSONCb('ciniki.foodmarket.dateBaskets', 
            {'business_id':M.curBusinessID, 'date_id':this.date_id, 'datestatus':'substitutions', 'outputs':'yes'}, 
            M.ciniki_foodmarket_main.menu.processBaskets);
    }
    this.menu.switchDate = function(s, i) {
        this.date_id = this.formValue(i);
        this.order_id = 0;
        this.packing_basket_id = 0;
        this.open();
    }
    this.menu.openOrder = function(oid) {
        this.order_id = oid;
        this.open();
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
    /* Products */
    this.menu.switchProductTab = function(t) {
        this.sections._product_tabs.selected = t;
        this.open(null,'products');
    }
    this.menu.openProducts = function(c, t) {
        if( c != null ) { 
            this.category_id = c; 
        }
        if( t != null ) {   
            this.sections.products.label = unescape(t);
        }
        this.open();
    }
    this.menu.newProductAdd = function(pid) {
        M.api.getJSONCb('ciniki.foodmarket.newList', {'business_id':M.curBusinessID, 'product_id':pid, 'action':'add'}, M.ciniki_foodmarket_main.menu.processNew);
    }
    this.menu.newProductRemove = function(pid) {
        M.api.getJSONCb('ciniki.foodmarket.newList', {'business_id':M.curBusinessID, 'product_id':pid, 'action':'remove'}, M.ciniki_foodmarket_main.menu.processNew);
    }
    this.menu.specialsAdd = function(i, p) {
        M.api.getJSONCb('ciniki.foodmarket.specialsList', {'business_id':M.curBusinessID, 'output_id':i, 'retail_sdiscount_percent':p}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.menu;
            p.size = 'large';
            p.data = rsp;
            p.refreshSection('specials_outputs');
            });
    }
    this.menu.specialsUpdate = function(e, s, i) {
        var p = this.formFieldValue(this.sections[s].fields.retail_sdiscount_percent, i);
        M.api.getJSONCb('ciniki.foodmarket.specialsList', {'business_id':M.curBusinessID, 'output_id':i, 'retail_sdiscount_percent':p}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.menu;
            p.size = 'large';
            p.data = rsp;
            p.refreshSection('specials_outputs');
            });
    }
    this.menu.open = function(cb, tab, itab, title) {
        this.data = {};
        if( cb != null ) { this.cb = cb; }
        if( tab != null ) { this.sections._tabs.selected = tab; }
        if( this.sections._tabs.selected == 'checkout' ) {
            M.api.getJSONCb('ciniki.poma.dateCheckout', 
                {'business_id':M.curBusinessID, 'date_id':this.date_id, 'order_id':this.order_id, 'customer_id':this.customer_id}, 
                M.ciniki_foodmarket_main.menu.processCheckout);
        } 
        else if( this.sections._tabs.selected == 'packing' && this.sections.packing_tabs.selected == 'orders' ) {
            M.api.getJSONCb('ciniki.foodmarket.datePacking', 
                {'business_id':M.curBusinessID, 'date_id':this.date_id, 'orders':'yes', 'order_id':this.order_id}, 
                M.ciniki_foodmarket_main.menu.processPacking);
        }
        else if( this.sections._tabs.selected == 'packing' && this.sections.packing_tabs.selected == 'baskets' ) {
            M.api.getJSONCb('ciniki.foodmarket.datePacking', 
                {'business_id':M.curBusinessID, 'date_id':this.date_id, 'baskets':'yes', 'packing_basket_id':this.packing_basket_id}, 
                M.ciniki_foodmarket_main.menu.processPacking);
        }
        else if( this.sections._tabs.selected == 'procurement' ) {
            M.api.getJSONCb('ciniki.foodmarket.procurement', 
                {'business_id':M.curBusinessID, 'date_id':this.date_id, 'supplier_id':this.supplier_id}, 
                M.ciniki_foodmarket_main.menu.processProcurement);
        } 
        else if( this.sections._tabs.selected == 'baskets' ) {
            M.api.getJSONCb('ciniki.foodmarket.dateBaskets', 
                {'business_id':M.curBusinessID, 'date_id':this.date_id, 'outputs':'yes'}, 
                M.ciniki_foodmarket_main.menu.processBaskets);
        }
        else if( this.sections._tabs.selected == 'availability' ) {
            M.api.getJSONCb('ciniki.foodmarket.dateItems', 
                {'business_id':M.curBusinessID, 'date_id':this.date_id}, 
                M.ciniki_foodmarket_main.menu.processAvailability);
        } 
        else if( this.sections._tabs.selected == 'dates' ) {
            M.api.getJSONCb('ciniki.poma.dateList', 
                {'business_id':M.curBusinessID}, 
                M.ciniki_foodmarket_main.menu.processDates);
        }
        else if( this.sections._tabs.selected == 'repeats' ) {
            M.api.getJSONCb('ciniki.foodmarket.customerRepeats', 
                {'business_id':M.curBusinessID, 'customers':'yes', 'customer_id':this.customer_id}, 
                M.ciniki_foodmarket_main.menu.processRepeats);
        }
        else if( this.sections._tabs.selected == 'queue' ) {
        }
        else if( this.sections._tabs.selected == 'favourites' ) {
            M.api.getJSONCb('ciniki.foodmarket.favouriteList', 
                {'business_id':M.curBusinessID, 'customers':'yes', 'customer_id':this.customer_id}, 
                M.ciniki_foodmarket_main.menu.processFavourites);
        } 
        else if( this.sections._tabs.selected == 'products' && this.sections._product_tabs.selected == 'specials' ) {
            M.api.getJSONCb('ciniki.foodmarket.specialsList', {'business_id':M.curBusinessID}, M.ciniki_foodmarket_main.menu.processSpecials);
        } 
        else if( this.sections._tabs.selected == 'products' && this.sections._product_tabs.selected == 'new' ) {
            M.api.getJSONCb('ciniki.foodmarket.newList', {'business_id':M.curBusinessID}, M.ciniki_foodmarket_main.menu.processNew);
        } 
        else if( this.sections._tabs.selected == 'products' && this.sections._product_tabs.selected == 'categories' ) {
            M.api.getJSONCb('ciniki.foodmarket.productList', 
                {'business_id':M.curBusinessID, 'categories':'yes', 'category_id':this.category_id}, 
                M.ciniki_foodmarket_main.menu.processProducts);
        } 
        else if( this.sections._tabs.selected == 'suppliers' ) {
            M.api.getJSONCb('ciniki.foodmarket.supplierList', 
                {'business_id':M.curBusinessID}, 
                M.ciniki_foodmarket_main.menu.processSuppliers);
        } 
        else if( this.sections._tabs.selected == 'notes' ) {
            M.api.getJSONCb('ciniki.poma.noteList', {'business_id':M.curBusinessID, 'customer_id':this.customer_id, 'customers':'yes'}, M.ciniki_foodmarket_main.menu.processNotes);
        } 
    }
    /* The following will process response from the API call */
    this.menu.processCheckout = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'large narrowaside';
        p.data = rsp;
        p.nplists = [];
        if( rsp.nplists != null ) {
            p.nplists = rsp.nplists;
        }
        p.sections._dates.fields.date_id.options = rsp.dates;
        if( rsp.date_id != null && rsp.date_id > 0 ) {
            p.date_id = rsp.date_id;
        }
        p.data.checkout_open_orders = rsp.open_orders;
        p.data.checkout_closed_orders = rsp.closed_orders;
        if( rsp.order != null ) {
            p.data.checkout_orderitems = rsp.order.items;
            p.data.checkout_tallies = rsp.order.tallies;
            p.data.checkout_payments = rsp.order.payments;
            p.data.checkout_ordermessages = rsp.order.messages;
        } else {
            p.data.checkout_orderitems = {};
            p.data.checkout_tallies = {};
            p.data.checkout_payments = {};
            p.data.checkout_ordermessages = {};
        }
        if( rsp.order != null && rsp.order.customer_id > 0 ) {
            p.order_id = rsp.order.id;
            p.customer_id = rsp.order.customer_id;
        }
        p.order_nplist = (rsp.order_nplist != null ? rsp.order_nplist : null);
        p.refresh();
        p.show();
    }
    this.menu.processPacking = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'large narrowaside';
        p.data = rsp;
        p.sections._dates.fields.date_id.options = rsp.dates;
        if( rsp.date_id != null && rsp.date_id > 0 ) {
            p.date_id = rsp.date_id;
        }
        p.sections.packing_basket_items.label = '';
        if( rsp.order_id > 0 ) {
            p.order_id = rsp.order_id;
        }
        if( p.packing_basket_id > 0 ) {
            p.sections.packing_basket_items.label = rsp.basket.billing_name + ' - ' + rsp.basket.description;
        }
        p.refresh();
        p.show();
    }
    this.menu.processProcurement = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'large narrowaside';
        p.data = rsp;
        p.sections._dates.fields.date_id.options = rsp.dates;
        if( rsp.date_id != null && rsp.date_id > 0 ) {
            p.date_id = rsp.date_id;
        }
        p.refresh();
        p.show();
    }
    this.menu.processBaskets = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'full';
        p.data = rsp;
        p.nplists = [];
        if( rsp.nplists != null ) {
            p.nplists = rsp.nplists;
        }
        p.sections._dates.fields.date_id.options = rsp.dates;
        if( rsp.date_id != null && rsp.date_id > 0 ) {
            p.date_id = rsp.date_id;
        }
        // Setup
        p.sections.baskets_items.headerValues = ['Supplier', 'Product', 'Price'];
        p.sections.baskets_items.headerClasses = ['Supplier', 'Product', 'Price'];
        p.sections.baskets_items.cellClasses = ['', '', ''];
        p.sections.baskets_items.footerClasses = ['', '', ''];
        p.sections.baskets_items.num_cols = 3;
        p.sections.baskets_items.basket_ids = [];
        for(var i in rsp.baskets) {
            p.sections.baskets_items.num_cols++;
            p.sections.baskets_items.headerValues.push(rsp.baskets[i].name.replace(/ .*/,'') + ' <span class="count">' + rsp.baskets[i].num_ordered + '</span>');
            p.sections.baskets_items.headerClasses.push('aligncenter');
            p.sections.baskets_items.cellClasses.push('aligncenter multiline');
            p.sections.baskets_items.footerClasses.push('aligncenter');
            p.sections.baskets_items.basket_ids.push(rsp.baskets[i].id);
        }
        p.sections.baskets_items.headerValues.push('Totals');
        p.sections.baskets_items.num_cols++;
        p.sections.baskets_items.headerValues.push('%');
        p.sections.baskets_items.num_cols++;
        p.sections.baskets_items.headerValues.push('');
        p.sections.baskets_items.num_cols++;
//        p.sections.baskets_items.headerValues.push('Profit');
//        p.sections.baskets_items.num_cols+=2;
        p.refresh();
        p.show();
    }
    this.menu.processAvailability = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'medium mediumaside';
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
    this.menu.processInventory = function(rsp) {
    }
    this.menu.processDates = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'xlarge';
        p.data = rsp;
        p.data.order_dates = rsp.dates;
        p.date_nplist = (rsp.date_nplist != null ? rsp.date_nplist : null);
        p.refresh();
        p.show();
    }
    this.menu.processRepeats = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'large narrowaside';
        p.data = rsp;
        p.data.customers.unshift({'id':'0', 'display_name':'All Customers'});
        if( p.customer_id > 0 ) {
            p.sections.customer_repeats.label = p.customer_name;
        } else {
            p.sections.customer_repeats.label = 'Favourites';
        }
        p.sections.customers.addTxt = 'Add';
        p.sections.customers.addFn = 'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_foodmarket_main.menu.open();\',\'mc\',{\'customer_id\':0, \'next\':\'M.ciniki_foodmarket_main.repeatitem.addCustomer\'});';
        p.refresh();
        p.show();
    }
    this.menu.processQueue = function(rsp) {
    }
    this.menu.processFavourites = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'large narrowaside';
        p.data = rsp;
        p.data.customers.unshift({'id':'0', 'display_name':'All Customers'});
        if( p.customer_id > 0 ) {
            p.sections.customer_favourites.label = p.customer_name;
        } else {
            p.sections.customer_favourites.label = 'Favourites';
        }
        p.sections.customers.addTxt = '';
        p.sections.customers.addFn = '';
        p.refresh();
        p.show();
    }
    this.menu.processSpecials = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'xlarge';
        p.data = rsp;
        p.refresh();
        p.show();
    }
    this.menu.processNew = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'xlarge';
        p.data = rsp;
        p.refresh();
        p.show();
    }
    this.menu.processProducts = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'large narrowaside';
        p.data = rsp;
        p.data.product_categories = rsp.categories;
        if( rsp.nextprevlist != null ) {
            p.nplist = rsp.nextprevlist;
        }
        if( p.category_id == 0 ) { p.sections.products.label = 'Uncategorized'; }
        if( p.category_id == -1 ) { p.sections.products.label = 'Archived'; }
        if( p.category_id == '' ) { p.sections.products.label = 'Latest'; }
        p.delButton('edit');
        if( p.category_id > 0 && p.sections._tabs.selected == 'products' ) {
            p.addButton('edit', 'Edit', 'M.ciniki_foodmarket_main.category.open(\'M.ciniki_foodmarket_main.menu.open();\',\'' + p.category_id + '\');');
        }
        p.refresh();
        p.show();
    }
    this.menu.processSuppliers = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'xlarge';
        p.data = rsp;
        if( rsp.nplist != null ) {
            p.nplist = rsp.nplist;
        }
        p.refresh();
        p.show();
    }
    this.menu.processNotes = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_foodmarket_main.menu;
        p.size = 'large narrowaside';
        p.data = rsp;
        p.data.customers.unshift({'id':'-1', 'display_name':'All Notes'});
        if( p.customer_id > 0 ) {
            p.sections.notes.label = p.customer_name;
        } else {
            p.sections.notes.label = 'Notes';
        }
        p.refresh();
        p.show();
    }
    this.menu.addClose('Back');

    //
    // The panel for editing a product
    //
    this.product = new M.panel('Product', 'ciniki_foodmarket_main', 'product', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.foodmarket.main.product');
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
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Private', '40':'Public', '90':'Archived'}},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'New'}}},
            'packing_order':{'label':'Packing', 'type':'toggle', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
            }},
        'basket':{'label':'', 'aside':'yes', 
            'visible':function() { return M.ciniki_foodmarket_main.product.sections.ptype.selected == '70' ? 'yes' : 'hidden';},
            'fields':{
                'basket_retail_price':{'label':'Price', 'type':'text', 'size':'small'},
                'basket_retail_taxtype_id':{'label':'Tax', 'type':'select', 'options':{}},
            }},
        '_legends':{'label':'Legends', 'aside':'yes',
            'addTxt':'Add Legend',
            'addFn':'M.ciniki_foodmarket_main.product.save("M.ciniki_foodmarket_main.legend.open(\'M.ciniki_foodmarket_main.product.refreshLegends();\',0,M.ciniki_foodmarket_main.product.product_id);");',
            'fields':{
                'legends':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':[], 'hint':'Enter a new legend: '},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'inputs', 'tabs':{
            'categories':{'label':'Categories', 
                'visible':function() {return M.modFlagSet('ciniki.foodmarket', 0x020);}, 
                'fn':'M.ciniki_foodmarket_main.product.selectTab("categories");'},
            'inputs':{'label':'Options', 
                'visible':function() { return M.ciniki_foodmarket_main.product.sections.ptype.selected == '10' ? 'yes' : 'no';},
                'fn':'M.ciniki_foodmarket_main.product.selectTab(\'inputs\');'},
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
                'input1_flags1':{'label':'Deposit', 'type':'flagtoggle', 'field':'input1_flags', 'bit':0x01, 'on_fields':['input1_cdeposit_name', 'input1_cdeposit_amount']},
//                'input1_flags':{'label':'Deposit', 'type':'toggle', 'visible':'yes', 'field':'input1_flags', 'mask':0x01, 'toggle':'yes', 'join':'yes', 
//                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
//                    'on_fields':['input1_cdeposit_name', 'input1_cdeposit_amount'],
//                    },
                'input1_cdeposit_name':{'label':'Invoice Item', 'visible':'no', 'type':'text'},
                'input1_cdeposit_amount':{'label':'Deposit', 'visible':'no', 'type':'text', 'size':'small'},
            }},
        'input1_10':{'label':'', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['10']); },
            'fields':{
                'input1_10_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_10_status':{'label':'Sell by Weight', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_10_units1', 'input1_10_flags2', 'input1_10_retail_percent', 'input1_10_retail_sdiscount_percent', 'input1_10_retail_price_calc'],
                    'on_sections':['input1_71'],
                    },
                'input1_10_units1':{'label':'Units', 'type':'flagspiece', 'visible':'no', 'field':'input1_10_units', 'mask':0xff, 'toggle':'yes', 'join':'yes', 
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel', 'flags':this.weightFlags,
                    },
                'input1_10_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_10_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_10_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_10_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_10_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
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
                'input1_71_retail_discount':{'label':'Discount', 'type':'toggle', 'visible':'no', 'default':'40', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 
                    'toggles':{'0':'0%', '0.05':'5%', '0.10':'10%', '0.15':'15%'},
                    },
                'input1_71_units':{'label':'', 'visible':'no', 'type':'text'},
                'input1_71_retail_percent':{'label':'', 'visible':'no', 'type':'text'},
                'input1_71_retail_price_calc':{'label':'Basket Price', 'type':'info', 'visible':'no', 'editable':'no'},
            }},
        'input1_30':{'label':'', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['30','50']); },
            'fields':{
                'input1_30_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_30_status':{'label':'Sell by Unit', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_30_units2', 'input1_30_flags2', 'input1_30_retail_percent', 'input1_30_retail_sdiscount_percent', 'input1_30_retail_price_calc'],
                    'on_sections':['input1_72'],
                    },
                'input1_30_units2':{'label':'Units', 'type':'flagspiece', 'visible':'no', 'field':'input1_30_units', 'mask':0x0f00, 'toggle':'yes', 'join':'yes', 'flags':this.unitFlags,
                    'onchange':'M.ciniki_foodmarket_main.product.updatePrices',
                    },
                'input1_30_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_30_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_30_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 
                    'toggles':this.pricePercentToggles,
                    },
                'input1_30_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_30_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
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
                'input1_72_retail_discount':{'label':'Discount', 'type':'toggle', 'visible':'no', 'default':'40', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 
                    'toggles':{'0':'0%', '0.05':'5%', '0.10':'10%', '0.15':'15%'},
                    },
                'input1_72_units':{'label':'', 'visible':'no', 'type':'text'},
                'input1_72_retail_percent':{'label':'', 'visible':'no', 'type':'text'},
                'input1_72_retail_price_calc':{'label':'Basket Price', 'type':'info', 'visible':'no', 'editable':'no'},
            }},
        'input1_20':{'label':'', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['20']); },
            'fields':{
                'input1_20_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_20_status':{'label':'Sell by Weighted Unit', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_20_units1', 'input1_20_units2', 'input1_20_flags2', 'input1_20_retail_percent', 'input1_20_retail_sdiscount_percent', 'input1_20_retail_price_calc'],
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
                'input1_20_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 
                    'toggles':this.pricePercentToggles,
                    },
                'input1_20_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_20_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
            }},
        'input1_50':{'label':'', 
            'visible':function() { return M.ciniki_foodmarket_main.product.inputVisible('inputs', 'input1', ['50']); },
            'fields':{
                'input1_50_id':{'label':'', 'visible':'no', 'type':'text'},
                'input1_50_status':{'label':'Sell by Case', 'type':'toggle', 'default':'5', 'toggles':{'5':'Inactive', '10':'Private', '40':'Public'},
                    'onchange':'M.ciniki_foodmarket_main.product.updatePanel',
                    'on_fields':['input1_50_flags2', 'input1_50_retail_percent', 'input1_50_retail_sdiscount_percent', 'input1_50_retail_price_calc'],
                    },
                'input1_50_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_50_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_50_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_50_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_50_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
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
                    'on_fields':['input1_52_name', 'input1_52_flags2', 'input1_52_retail_percent', 'input1_52_retail_sdiscount_percent', 'input1_52_retail_price_calc'],
                    },
                'input1_52_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/2 case'},
                'input1_52_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_52_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_52_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_52_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_52_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
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
                    'on_fields':['input1_53_name', 'input1_53_flags2', 'input1_53_retail_percent', 'input1_53_retail_sdiscount_percent', 'input1_53_retail_price_calc'],
                    },
                'input1_53_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/3 case'},
                'input1_53_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_53_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_53_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_53_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_53_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
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
                    'on_fields':['input1_54_name', 'input1_54_flags2', 'input1_54_retail_percent', 'input1_54_retail_sdiscount_percent', 'input1_54_retail_price_calc'],
                    },
                'input1_54_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/4 case'},
                'input1_54_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_54_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_54_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_54_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_54_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
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
                    'on_fields':['input1_55_name', 'input1_55_flags2', 'input1_55_retail_percent', 'input1_55_retail_sdiscount_percent', 'input1_55_retail_price_calc'],
                    },
                'input1_55_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/5 case'},
                'input1_55_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_55_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_55_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_55_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_55_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
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
                    'on_fields':['input1_56_name', 'input1_56_flags2', 'input1_56_retail_percent', 'input1_56_retail_sdiscount_percent', 'input1_56_retail_price_calc'],
                    },
                'input1_56_name':{'label':'Label', 'type':'text', 'visible':'no', 'hint':'1/6 case'},
                'input1_56_flags2':{'label':'Availability', 'type':'flagspiece', 'visible':'no', 'field':'input1_56_flags', 'mask':0x1F00, 'toggle':'yes', 'join':'yes', 
                    'flags':{'9':{'name':'Always'}, '10':{'name':'Dates'}, '11':{'name':'Queue'}, '12':{'name':'Limited'}},
                    },
                'input1_56_retail_percent':{'label':'Cost +', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.pricePercentToggles},
                'input1_56_retail_sdiscount_percent':{'label':'Specials Discount', 'type':'toggle', 'visible':'no', 'onchange':'M.ciniki_foodmarket_main.product.updatePrices', 'toggles':this.priceSpecialsPercentToggles},
                'input1_56_retail_price_calc':{'label':'Price', 'type':'info', 'visible':'no', 'editable':'no'},
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
    this.product.refreshLegends = function() {
        M.api.getJSONCb('ciniki.foodmarket.productGet', {'business_id':M.curBusinessID, 'product_id':this.product_id, 'legends':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.product;
            p.sections._legends.fields.legends.list = rsp.legends;
            p.refreshSection('_legends');
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
            var precision = 2;
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
                    if( auc < 1 ) {
                        precision = 4;
                    }
                    this.data['input' + i + '_10_retail_price_calc'] = '$' + (auc * (1+rp10)).toFixed(precision) + M.ciniki_foodmarket_main.unitSuffix(opu);
                    // Calculate basket retail price based on retail price and basket discount
                    var rd71 = this.formValue('input' + i + '_71_retail_discount');
                    if( rd71 != '' ) {
                        rd71 = parseFloat(rd71);
                        rp71 = (1+rp10) - ((1+rp10)*rd71) - 1;
                        if( rp71 < 0 ) {
                            rp71 = 0;               // Can't be below zero, otherwise below cost
                        }
                        this.data['input' + i + '_71_retail_price_calc'] = '$' + (auc * (1+rp71)).toFixed(precision) + M.ciniki_foodmarket_main.unitSuffix(opu);
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
                    if( auc < 1 ) {
                        precision = 4;
                    }
                    this.data['input' + i + '_20_retail_price_calc'] = '$' + (auc * (1+rp20)).toFixed(precision) + M.ciniki_foodmarket_main.unitSuffix(opu);
                } else {
                    this.data['input' + i + '_20_retail_price_calc'] = '';
                }
                //
                // Calculate the retail price for sell by unit base on percent
                //
                var rp30 = this.formValue('input' + i + '_30_retail_percent');
                var rsdp30 = this.formValue('input' + i + '_30_retail_sdiscount_percent');
                var opu = this.formValue('input' + i + '_30_units2');   // output weight type
                if( rp30 != '' ) {  
                    rp30 = parseFloat(rp30);
                    var price = (unitcost * (1+rp30)).toFixed(2);
                    rsdp30 = parseFloat(rsdp30);
                    if( rsdp30 != null && rsdp30 > 0 ) {    
                        var discount = (price * rsdp30).toFixed(2);
                        this.data['input' + i + '_30_retail_price_calc'] = '<s>$' + price + '</s> $' + (price - discount).toFixed(2) + M.ciniki_foodmarket_main.unitSuffix(opu&0xff00);
                    } else {
                        this.data['input' + i + '_30_retail_price_calc'] = '$' + price + M.ciniki_foodmarket_main.unitSuffix(opu&0xff00);
                    }
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
            this.sections[s].fields[s + '_cdeposit_name'].visible = ((flags&0x01) == 0x01 ? 'yes' : 'no');
            this.sections[s].fields[s + '_cdeposit_amount'].visible = ((flags&0x01) == 0x01 ? 'yes' : 'no');
        } else {
            var v = this.formValue(s + '_itype');
            var u1 = this.formValue(s + '_units1');
            var u2 = this.formValue(s + '_units2');
            var flags2 = this.formValue(s + '_flags2');
            this.sections[s].fields[s + '_inventory'].visible = (flags2 == 'on' ? 'yes' : 'no');
            var flags1 = this.formValue(s + '_flags1');
            this.sections[s].fields[s + '_cdeposit_name'].visible = (flags1 == 'on' ? 'yes' : 'no');
            this.sections[s].fields[s + '_cdeposit_amount'].visible = (flags1 == 'on' ? 'yes' : 'no');
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
        this.showHideFormField(s, s + '_cdeposit_name');
        this.showHideFormField(s, s + '_cdeposit_amount');
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
        var args = {'business_id':M.curBusinessID, 'product_id':this.product_id, 'categories':'yes', 'legends':'yes', 'suppliers':'yes'};
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
            p.sections._legends.fields.legends.list = rsp.legends;
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
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_foodmarket_main.category.setFieldValue('image_id', iid, null, null);
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
    // The panel for editing a legend or child legend
    //
    this.legend = new M.panel('Category', 'ciniki_foodmarket_main', 'legend', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.foodmarket.main.legend');
    this.legend.data = {};
    this.legend.legend_id = 0;
    this.legend.sections = { 
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_foodmarket_main.legend.setFieldValue('image_id', iid, null, null);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                        M.ciniki_foodmarket_main.legend.setFieldValue(fid, 0, null, null);
                        return true;
                    },
                },
            }},
        'general':{'label':'Product', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'code':{'label':'Code', 'type':'text', 'size':'small'},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.legend.save();'},
            'delete':{'label':'Delete', 'visible':function() {return M.ciniki_foodmarket_main.legend.legend_id>0?'yes':'no';}, 'fn':'M.ciniki_foodmarket_main.legend.remove();'},
            }},
        };  
    this.legend.fieldValue = function(s, i, d) { return this.data[i]; }
    this.legend.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.foodmarket.legendHistory', 'args':{'business_id':M.curBusinessID, 'legend_id':this.legend_id, 'field':i}};
    }
    this.legend.open = function(cb, id, list) {
        this.reset();
        if( id != null ) { this.legend_id = id; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.foodmarket.legendGet', {'business_id':M.curBusinessID, 'legend_id':this.legend_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.legend;
            p.data = rsp.legend;
            p.refresh();
            p.show(cb);
        });
    }
    this.legend.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.legend.close();'; }
        if( this.legend_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.foodmarket.legendUpdate', {'business_id':M.curBusinessID, 'legend_id':this.legend_id}, c,
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
            M.api.postJSONCb('ciniki.foodmarket.legendAdd', {'business_id':M.curBusinessID, 'legend_id':this.legend_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_foodmarket_main.legend.legend_id = rsp.id;
                    eval(cb);
                });
        }
    };
    this.legend.remove = function() {
        if( confirm('Are you sure you want to remove this legend?') ) {
            M.api.getJSONCb('ciniki.foodmarket.legendDelete', {'business_id':M.curBusinessID, 'legend_id':this.legend_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_foodmarket_main.legend.close();
            });
        }
    };
    this.legend.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.legend_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_foodmarket_main.legend.save(\'M.ciniki_foodmarket_main.legend.open(null,' + this.nplist[this.nplist.indexOf('' + this.legend_id) + 1] + ');\');';
        }
        return null;
    }
    this.legend.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.legend_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.legend.save(\'M.ciniki_foodmarket_main.legend.open(null,' + this.nplist[this.nplist.indexOf('' + this.legend_id) - 1] + ');\');';
        }
        return null;
    }
    this.legend.addButton('save', 'Save', 'M.ciniki_foodmarket_main.legend.save();');
    this.legend.addClose('Cancel');
    this.legend.addButton('next', 'Next');
    this.legend.addLeftButton('prev', 'Prev');

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
    // The panel to edit Order Date
    //
    this.editdate = new M.panel('Order Date', 'ciniki_foodmarket_main', 'editdate', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.editdate');
    this.editdate.data = null;
    this.editdate.date_id = 0;
    this.editdate.nplist = [];
    this.editdate.sections = {
        'general':{'label':'', 'fields':{
            'order_date':{'label':'Date', 'required':'yes', 'type':'date'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Open', '20':'Repeats Added', '30':'Substitutions', '50':'Locked', '90':'Closed'}},
            'flags1':{'label':'Autolock', 'type':'flagtoggle', 'field':'flags', 'bit':0x01, 'on_fields':['autolock_date', 'autolock_time']},
            'autolock_date':{'label':'Auto Lock Date', 'visible':'no', 'type':'date'},
            'autolock_time':{'label':'Auto Lock Time', 'visible':'no', 'type':'text', 'size':'small'},
            }},
        '_repeats':{'label':'Apply repeats on', 'fields':{
            'repeats_date':{'label':'Date', 'type':'date'},
            'repeats_time':{'label':'Time', 'type':'text', 'size':'small'},
            }},
        '_notices':{'label':'Notices', 'fields':{
            'notices':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.editdate.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_foodmarket_main.editdate.date_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_foodmarket_main.editdate.remove();'},
            }},
        };
    this.editdate.fieldValue = function(s, i, d) { return this.data[i]; }
    this.editdate.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.poma.dateHistory', 'args':{'business_id':M.curBusinessID, 'date_id':this.date_id, 'field':i}};
    }
    this.editdate.open = function(cb, did, list) {
        if( did != null ) { this.date_id = did; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.poma.dateGet', {'business_id':M.curBusinessID, 'date_id':this.date_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.editdate;
            p.data = rsp.date;
            if( (rsp.date.flags&0x01) == 0x01 ) {
                p.sections.general.fields.autolock_date.visible = 'yes';
                p.sections.general.fields.autolock_time.visible = 'yes';
            } else {
                p.sections.general.fields.autolock_date.visible = 'no';
                p.sections.general.fields.autolock_time.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.editdate.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.editdate.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.date_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.poma.dateUpdate', {'business_id':M.curBusinessID, 'date_id':this.date_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.poma.dateAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.editdate.date_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.editdate.remove = function() {
        if( confirm('Are you sure you want to remove order date?') ) {
            M.api.getJSONCb('ciniki.poma.dateDelete', {'business_id':M.curBusinessID, 'date_id':this.date_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.editdate.close();
            });
        }
    }
    this.editdate.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.date_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_foodmarket_main.editdate.save(\'M.ciniki_foodmarket_main.editdate.open(null,' + this.nplist[this.nplist.indexOf('' + this.date_id) + 1] + ');\');';
        }
        return null;
    }
    this.editdate.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.date_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.editdate.save(\'M.ciniki_foodmarket_main.date_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.date_id) - 1] + ');\');';
        }
        return null;
    }
    this.editdate.addButton('save', 'Save', 'M.ciniki_foodmarket_main.editdate.save();');
    this.editdate.addClose('Cancel');
    this.editdate.addButton('next', 'Next');
    this.editdate.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Order Item
    //
    this.orderitem = new M.panel('Order Item', 'ciniki_foodmarket_main', 'orderitem', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.orderitem');
    this.orderitem.data = null;
    this.orderitem.item_id = 0;
    this.orderitem.order_id = 0;
    this.orderitem.object = '';
    this.orderitem.object_id = 0;
    this.orderitem.nplist = [];
    this.orderitem.sections = {
        'general':{'label':'', 'fields':{
//            'flags':{'label':'Options', 'type':'text'},
//            'object':{'label':'Object', 'type':'text'},
//            'object_id':{'label':'Object ID', 'type':'text'},
//            'code':{'label':'Code', 'type':'text'},
            'description':{'label':'Item', 'required':'yes', 'type':'text', 'autofocus':'yes', 'livesearch':'yes', 'livesearchcols':2},
            'itype':{'label':'Sold By', 'required':'yes', 'type':'toggle', 
                'toggles':{'10':'Weight', '20':'Weighted Units', '30':'Units'}, 
                'onchange':'M.ciniki_foodmarket_main.orderitem.updateForm', 
                },
            'unit_quantity':{'label':'Unit Quantity', 'visible':'no', 'type':'text', 'size':'small'},
            'weight_quantity':{'label':'Weight', 'visible':'no', 'type':'text', 'size':'small'},
            'weight_units':{'label':'Weight Units', 'visible':'no', 'type':'toggle', 'toggles':{'20':'lb', '25':'oz', '60':'kg', '65':'g'}},
            'unit_amount':{'label':'Unit Amount', 'required':'yes', 'type':'text', 'size':'small'},
            'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
            'unit_discount_percentage':{'label':'Discount Percentage', 'type':'text', 'size':'small'},
            'unit_suffix':{'label':'Unit Suffix', 'visible':'no', 'type':'text', 'size':'small'},
            'packing_order':{'label':'Packing', 'type':'toggle', 'toggles':{'10':'Top', '50':'Middle', '90':'Bottom'}},
//            'taxtype_id':{'label':'Tax Type', 'type':'text'},
            'flags6':{'label':'Locked', 'type':'flagtoggle', 'field':'flags', 'bit':0x20},
            'flags1':{'label':'Deposit', 'type':'flagtoggle', 'field':'flags', 'bit':0x80, 'on_fields':['cdeposit_description', 'cdeposit_amount']},
            'cdeposit_description':{'label':'Invoice Item', 'visible':'no', 'type':'text'},
            'cdeposit_amount':{'label':'Deposit', 'visible':'no', 'type':'text', 'size':'small'},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_move_button':{'label':'', 
            'visible':function() { return (M.ciniki_foodmarket_main.orderitem.data.order_id > 0 ? 'yes' : 'no'); },
            'buttons':{
                'move':{'label':'Move Item', 'fn':'M.ciniki_foodmarket_main.orderitem.move();'},
            }},
        'orderdates':{'label':'Move to', 'type':'simplegrid', 'num_cols':1, 'visible':'hidden',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.orderitem.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_foodmarket_main.orderitem.item_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_foodmarket_main.orderitem.remove();'},
            }},
    }
    this.orderitem.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.poma.orderItemSearch', {'business_id':M.curBusinessID,
            'field':i, 'order_id':M.ciniki_foodmarket_main.orderitem.order_id, 'start_needle':v, 'limit':25}, function(rsp) {
            M.ciniki_foodmarket_main.orderitem.liveSearchShow(s,i,M.gE(M.ciniki_foodmarket_main.orderitem.panelUID + '_' + i), rsp.items);
           });
    }
    this.orderitem.liveSearchResultClass = function(s, f, i, j, d) {
        return 'multiline';
    }
    this.orderitem.liveSearchResultValue = function(s,f,i,j,d) {
        switch(j) {
            case 0: 
                if( d.notes != null && d.notes != '' ) {
                    return '<span class="maintext">' + d.description + '</span><span class="subtext">' + d.notes + '</span>';
                }
                return d.description;
            case 1:
                if( d.discount_text != null && d.discount_text != '' ) {
                    return '<span class="maintext">' + d.unit_amount_text + '</span><span class="subtext">' + d.discount_text + '</span>';
                }
                return d.unit_price_text;
            case 2: 
                if( d.taxtype_name != null && d.taxtype_name != '' ) {
                    return '<span class="maintext">' + d.total_text + '</span><span class="subtext">' + d.taxtype_name + '</span>';
                }
                return d.total_text;
        }
        return '';
    }
    this.orderitem.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_foodmarket_main.orderitem.updateFromSearch(\'' + s + '\',\'' + f + '\',\'' + d.object + '\',\'' + d.object_id + '\',\'' + escape(d.description) + '\',\'' + d.itype + '\',\'' + d.weight_units + '\',\'' + d.weight_quantity + '\',\'' + d.unit_quantity + '\',\'' + escape(d.unit_suffix) + '\',\'' + d.packing_order + '\',\'' + d.unit_amount_text + '\',\'' + d.unit_discount_amount + '\',\'' + d.unit_discount_percentage + '\',\'' + d.taxtype_id + '\',\'' + escape(d.notes) + '\');';
    }
    this.orderitem.updateFromSearch = function(s,f,o,oid,d,i,wu,wq,uq,us,po,ua,da,dp,t,n) {
        this.object = o;
        this.object_id = oid;
        this.setFieldValue('itype', i);
        this.updateForm();
        this.setFieldValue('description', unescape(d));
        this.setFieldValue('weight_units', wu);
        this.setFieldValue('weight_quantity', wq);
        this.setFieldValue('unit_quantity', uq);
        this.setFieldValue('unit_suffix', unescape(us));
        this.setFieldValue('packing_order', po);
        this.setFieldValue('unit_amount', ua);
        this.setFieldValue('unit_discount_amount', da);
        this.setFieldValue('unit_discount_percentage', dp);
        if( M.curBusiness.modules['ciniki.taxes'] != null ) {
            this.setFieldValue('taxtype_id', t);
        }
        this.setFieldValue('notes', unescape(n));
        this.removeLiveSearch(s, f);
        if( i < 30 ) {
            M.gE(this.panelUID + '_weight_quantity').focus();
        } else {
            M.gE(this.panelUID + '_unit_quantity').focus();
        }
    }
    this.orderitem.fieldValue = function(s, i, d) { return this.data[i]; }
    this.orderitem.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.poma.orderItemHistory', 'args':{'business_id':M.curBusinessID, 'item_id':this.item_id, 'field':i}};
    }
    this.orderitem.cellValue = function(s, i, j, d) {
        return d.name_status;
    }
    this.orderitem.rowFn = function(s, i, d) {
        return 'M.ciniki_foodmarket_main.orderitem.save(null,\'' + d.id + '\');';
    }
    this.orderitem.move = function() {
        this.sections.orderdates.visible = 'yes';
        this.refreshSection('orderdates');
    }
    this.orderitem.updateForm = function() {
        var v = this.formValue('itype');
        if( v == '10' ) {
            this.sections.general.fields.weight_quantity.visible = 'yes';
            this.sections.general.fields.weight_units.visible = 'yes';
            this.sections.general.fields.unit_quantity.visible = 'no';
            this.sections.general.fields.unit_suffix.visible = 'no';
        } else if( v == '20' ) {
            this.sections.general.fields.weight_quantity.visible = 'yes';
            this.sections.general.fields.weight_units.visible = 'yes';
            this.sections.general.fields.unit_quantity.visible = 'yes';
            this.sections.general.fields.unit_suffix.visible = 'no';
        } else if( v == '30' ) {
            this.sections.general.fields.weight_quantity.visible = 'no';
            this.sections.general.fields.weight_units.visible = 'no';
            this.sections.general.fields.unit_quantity.visible = 'yes';
            this.sections.general.fields.unit_suffix.visible = 'yes';
        }
        this.refreshFormField('general', 'weight_quantity');
        this.refreshFormField('general', 'weight_units');
        this.refreshFormField('general', 'unit_quantity');
        this.refreshFormField('general', 'unit_suffix');
    }
    this.orderitem.open = function(cb, iid, oid, list) {
        if( iid != null ) { this.item_id = iid; }
        if( list != null ) { this.nplist = list; }
        if( oid != null ) { this.order_id = oid; }
        this.sections.orderdates.visible = 'hidden';
        this.sections.general.fields.description.autofocus = (this.item_id > 0 ? 'no' : 'yes');
        M.api.getJSONCb('ciniki.poma.orderItemGet', {'business_id':M.curBusinessID, 'item_id':this.item_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.orderitem;
            p.data = rsp.item;
            p.data.orderdates = rsp.orderdates;
            if( p.item_id == 0 ) {
                p.object = '';
                p.object_id = 0;
            }
            p.refresh();
            p.show(cb);
            p.updateForm();
        });
    }
    this.orderitem.save = function(cb, date_id) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.orderitem.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.item_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' || date_id != null ) {
                M.api.postJSONCb('ciniki.poma.orderItemUpdate', 
                    {'business_id':M.curBusinessID, 'item_id':this.item_id, 'object':this.object, 'object_id':this.object_id, 'date_id':(date_id != null ? date_id : 0)}, 
                    c, function(rsp) {
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
            M.api.postJSONCb('ciniki.poma.orderItemAdd', 
                {'business_id':M.curBusinessID, 'order_id':this.order_id, 'object':this.object, 'object_id':this.object_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_foodmarket_main.orderitem.item_id = rsp.id;
                    eval(cb);
                });
        }
    }
    this.orderitem.remove = function() {
        if( confirm('Are you sure you want to remove orderitem?') ) {
            M.api.getJSONCb('ciniki.poma.orderItemDelete', {'business_id':M.curBusinessID, 'item_id':this.item_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.orderitem.close();
            });
        }
    }
    this.orderitem.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.item_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_foodmarket_main.orderitem.save(\'M.ciniki_foodmarket_main.orderitem.open(null,' + this.nplist[this.nplist.indexOf('' + this.item_id) + 1] + ');\');';
        }
        return null;
    }
    this.orderitem.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.item_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.orderitem.save(\'M.ciniki_foodmarket_main.orderitem.open(null,' + this.nplist[this.nplist.indexOf('' + this.item_id) - 1] + ');\');';
        }
        return null;
    }
    this.orderitem.addButton('save', 'Save', 'M.ciniki_foodmarket_main.orderitem.save();');
    this.orderitem.addClose('Cancel');
    this.orderitem.addButton('next', 'Next');
    this.orderitem.addLeftButton('prev', 'Prev');

    //
    // The panel to update a customer favourite
    //
    this.repeatitem = new M.panel('Standing Order Item', 'ciniki_foodmarket_main', 'repeatitem', 'mc', 'large', 'sectioned', 'ciniki.foodmarket.main.repeatitem');
    this.repeatitem.data = null;
    this.repeatitem.item_id = 0;
    this.repeatitem.object = '';
    this.repeatitem.object_id = 0;
    this.repeatitem.nplist = [];
    this.repeatitem.sections = {
        'general':{'label':'', 'fields':{
            'description':{'label':'Item', 'required':'yes', 'type':'text', 'livesearch':'yes', 'livesearchcols':2},
            'quantity':{'label':'Quantity', 'type':'text', 'size':'small'},
            'repeat_days':{'label':'Repeat', 'type':'toggle', 'toggles':{'7':'weekly', '14':'2 weeks'}},
            'next_order_date':{'label':'Next Date', 'type':'date'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.repeatitem.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_foodmarket_main.repeatitem.item_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_foodmarket_main.repeatitem.remove();'},
            }},
    }
    this.repeatitem.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.poma.repeatItemSearch', {'business_id':M.curBusinessID,
            'field':i, 'start_needle':v, 'limit':25}, function(rsp) {
            M.ciniki_foodmarket_main.repeatitem.liveSearchShow(s,i,M.gE(M.ciniki_foodmarket_main.repeatitem.panelUID + '_' + i), rsp.items);
           });
    }
    this.repeatitem.liveSearchResultClass = function(s, f, i, j, d) {
        return 'multiline';
    }
    this.repeatitem.liveSearchResultValue = function(s,f,i,j,d) {
        switch(j) {
            case 0: return d.description;
        }
        return '';
    } 
    this.repeatitem.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_foodmarket_main.repeatitem.updateFromSearch(\'' + s + '\',\'' + f + '\',\'' + d.object + '\',\'' + d.object_id + '\',\'' + escape(d.description) + '\');';
    }
    this.repeatitem.updateFromSearch = function(s,f,o,oid,d) {
        this.object = o;
        this.object_id = oid;
        this.setFieldValue('description', unescape(d));
        this.removeLiveSearch(s, f);
    } 
    this.repeatitem.fieldValue = function(s, i, d) { return this.data[i]; }
    this.repeatitem.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.poma.customerItemHistory', 'args':{'business_id':M.curBusinessID, 'item_id':this.item_id, 'field':i}};
    }
    this.repeatitem.addCustomer = function(cid) {
        this.open('M.ciniki_foodmarket_main.menu.open();', 0, cid);
    }
    this.repeatitem.open = function(cb, iid, cid, list) {
        if( iid != null ) { this.item_id = iid; }
        if( list != null ) { this.nplist = list; }
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.poma.customerItemGet', {'business_id':M.curBusinessID, 'item_id':this.item_id, 'type':'40'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.repeatitem;
            p.data = rsp.item;
            p.object = rsp.item.object;
            p.object_id = rsp.item.object_id;
            p.refresh();
            p.show(cb);
        });
    }
    this.repeatitem.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.repeatitem.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.item_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                if( this.object == '' || this.object_id == 0 ) {
                    alert('You must specify a product from the dropdown list.');
                    return false;
                }
                M.api.postJSONCb('ciniki.poma.customerItemUpdate', 
                    {'business_id':M.curBusinessID, 'item_id':this.item_id, 'object':this.object, 'object_id':this.object_id}, 
                    c, function(rsp) {
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
            if( this.object == '' || this.object_id == 0 ) {
                alert('You must specify a product from the dropdown list.');
                return false;
            }
            M.api.postJSONCb('ciniki.poma.customerItemAdd', 
                {'business_id':M.curBusinessID, 'object':this.object, 'object_id':this.object_id, 'customer_id':this.customer_id, 'itype':'40'}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_foodmarket_main.repeatitem.item_id = rsp.id;
                    eval(cb);
                });
        }
    }
    this.repeatitem.remove = function() {
        if( confirm('Are you sure you want to remove repeatitem?') ) {
            M.api.getJSONCb('ciniki.poma.customerItemDelete', {'business_id':M.curBusinessID, 'item_id':this.item_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.repeatitem.close();
            });
        }
    }
    this.repeatitem.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.item_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_foodmarket_main.repeatitem.save(\'M.ciniki_foodmarket_main.repeatitem.open(null,' + this.nplist[this.nplist.indexOf('' + this.item_id) + 1] + ');\');';
        }
        return null;
    }
    this.repeatitem.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.item_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.repeatitem.save(\'M.ciniki_foodmarket_main.repeatitem.open(null,' + this.nplist[this.nplist.indexOf('' + this.item_id) - 1] + ');\');';
        }
        return null;
    }
    this.repeatitem.addButton('save', 'Save', 'M.ciniki_foodmarket_main.repeatitem.save();');
    this.repeatitem.addClose('Cancel');
    this.repeatitem.addButton('next', 'Next');
    this.repeatitem.addLeftButton('prev', 'Prev');

    //
    // The edit panel for ledger credits
    //
    this.ledgerentry = new M.panel('Customer Credit', 'ciniki_foodmarket_main', 'ledgerentry', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.ledgerentry');
    this.ledgerentry.data = null;
    this.ledgerentry.customer_id = 0;
    this.ledgerentry.transaction_type = 0;
    this.ledgerentry.entry_id = 0;
    this.ledgerentry.nplist = [];
    this.ledgerentry.sections = {
        'general':{'label':'', 'fields':{
            'transaction_date_date':{'label':'Date', 'type':'date',
                'visible':function() { return (M.ciniki_foodmarket_main.ledgerentry.entry_id > 0 ?'yes':'no'); },
                },
            'transaction_date_time':{'label':'Time', 'type':'text', 'size':'small',
                'visible':function() { return (M.ciniki_foodmarket_main.ledgerentry.entry_id > 0 ?'yes':'no'); },
                },
            'source':{'label':'Source', 'type':'toggle', 'toggles':{'90':'Interac', '100':'Cash', '105':'Cheque', '110':'Email', '120':'Other'},
                'visible':function() { return (M.ciniki_foodmarket_main.ledgerentry.transaction_type==60?'yes':'no'); },
                },
            'customer_amount':{'label':'Amount', 'required':'yes', 'type':'text', 'size':'small'}
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.ledgerentry.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_foodmarket_main.ledgerentry.entry_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_foodmarket_main.ledgerentry.remove();'},
            }},
    }
    this.ledgerentry.fieldValue = function(s, i, d) { return this.data[i]; }
    this.ledgerentry.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.poma.customerLedgerHistory', 'args':{'business_id':M.curBusinessID, 'entry_id':this.entry_id, 'field':i}};
    }
    this.ledgerentry.open = function(cb, eid, t, amt, cid) {
        if( eid != null ) { this.entry_id = eid; }
        if( t != null ) { this.transaction_type = t; }
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.poma.customerLedgerGet', {'business_id':M.curBusinessID, 'entry_id':this.entry_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.ledgerentry;
            p.data = rsp.entry;
            if( p.entry_id == 0 && p.data.customer_amount == '' && parseFloat(amt) > 0 ) {
                p.data.customer_amount = parseFloat(amt).toFixed(2);
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.ledgerentry.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.ledgerentry.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.entry_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.poma.customerLedgerUpdate', 
                    {'business_id':M.curBusinessID, 'entry_id':this.entry_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.poma.customerLedgerAdd', 
                {'business_id':M.curBusinessID, 'entry_id':this.entry_id, 'transaction_type':this.transaction_type, 'customer_id':this.customer_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_foodmarket_main.ledgerentry.entry_id = rsp.id;
                    eval(cb);
                });
        }
    }
    this.ledgerentry.remove = function() {
        if( confirm('Are you sure you want to remove ledgerentry?') ) {
            M.api.getJSONCb('ciniki.poma.customerLedgerDelete', {'business_id':M.curBusinessID, 'entry_id':this.entry_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.ledgerentry.close();
            });
        }
    }
    this.ledgerentry.addButton('save', 'Save', 'M.ciniki_foodmarket_main.ledgerentry.save();');
    this.ledgerentry.addClose('Cancel');

    //
    // The edit invoice panel
    //
    this.email = new M.panel('Email Invoice', 'ciniki_foodmarket_main', 'email', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.email');
    this.email.order_id = 0;
    this.email.data = {};
    this.email.sections = {
        '_subject':{'label':'', 'fields':{
            'subject':{'label':'Subject', 'type':'text', 'history':'no'},
            }},
        '_textmsg':{'label':'Message', 'fields':{
            'textmsg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large', 'history':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'send':{'label':'Send', 'fn':'M.ciniki_foodmarket_main.email.send();'},
            }},
    };
    this.email.fieldValue = function(s, i, d) {
        return this.data[i];
    };
/*    this.email.emailOrder = function(cb, order) {
        this.order_id = order.id;
        this.data.subject = 'Invoice #' + order.order_number;
        this.data.textmsg = '';
        this.open(cb);
    }; */
    this.email.open = function(cb, oid) {
        if( oid != null ) { this.order_id = oid; }
        //
        // Get the email template
        //
        M.api.getJSONCb('ciniki.poma.orderEmailGet', {'business_id':M.curBusinessID, 'order_id':this.order_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.email;
            p.data = rsp.email;
            p.refresh();
            p.show(cb);
            });
    };
    this.email.send = function() {
        var subject = this.formFieldValue(this.sections._subject.fields.subject, 'subject');
        var textmsg = this.formFieldValue(this.sections._textmsg.fields.textmsg, 'textmsg');
        M.api.getJSONCb('ciniki.poma.invoicePDF', {'business_id':M.curBusinessID, 
            'order_id':this.order_id, 'subject':subject, 'textmsg':textmsg, 'output':'pdf', 'email':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.email.close();
            });
    };
    this.email.addClose('Cancel');

    this.printcatalog = new M.panel('Print Catalog', 'ciniki_foodmarket_main', 'printcatalog', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.printcatalog');
    this.printcatalog.data = {};
    this.printcatalog.sections = {
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'print', 'tabs':{
            'print':{'label':'Print', 'fn':'M.ciniki_foodmarket_main.printcatalog.switchTab("print");'},
            'email':{'label':'Email', 'fn':'M.ciniki_foodmarket_main.printcatalog.switchTab("email");'},
            }},
        '_categories':{'label':'Categories', 'fields':{
            'categories':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':[]},
            }},
        '_subscriptions':{'label':'', 
            'visible':function() {return (M.ciniki_foodmarket_main.printcatalog.sections._tabs.selected=='email'?'yes':'hidden'); },
            'fields':{
                'subscriptions':{'label':'Send To', 'type':'idlist', 'list':[]},
            }},
        '_subject':{'label':'', 
            'visible':function() {return (M.ciniki_foodmarket_main.printcatalog.sections._tabs.selected=='email'?'yes':'hidden'); },
            'fields':{
                'subject':{'label':'Subject', 'type':'text', 'history':'no'},
            }},
        '_textmsg':{'label':'Message', 
            'visible':function() {return (M.ciniki_foodmarket_main.printcatalog.sections._tabs.selected=='email'?'yes':'hidden'); },
            'fields':{
                'textmsg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large', 'history':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'print':{'label':'Download PDF', 
                'visible':function() {return (M.ciniki_foodmarket_main.printcatalog.sections._tabs.selected=='print'?'yes':'no'); },
                'fn':'M.ciniki_foodmarket_main.printcatalog.downloadPDF();'},
            'emailtest':{'label':'Send Test Email PDF', 
                'visible':function() {return (M.ciniki_foodmarket_main.printcatalog.sections._tabs.selected=='email'?'yes':'no'); },
                'fn':'M.ciniki_foodmarket_main.printcatalog.emailTestPDF();'},
            'email':{'label':'Email PDF', 
                'visible':function() {return (M.ciniki_foodmarket_main.printcatalog.sections._tabs.selected=='email'?'yes':'no'); },
                'fn':'M.ciniki_foodmarket_main.printcatalog.emailPDF();'},
            }},
        }
    this.printcatalog.fieldValue = function(s, i, j) {
        return this.data[i];
    }
    this.printcatalog.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSection('_tabs');
        this.refreshSection('_buttons');
        this.showHideSection('_subscriptions');
        this.showHideSection('_subject');
        this.showHideSection('_textmsg');
    }
    this.printcatalog.open = function(cb) {
        M.api.getJSONCb('ciniki.foodmarket.categoryList', {'business_id':M.curBusinessID, 'subscriptions':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.printcatalog;
            p.data.categories = [];
            for(var i in rsp.categories) {
                p.data.categories.push(rsp.categories[i].id);
            }
            p.data.subscriptions = [];
            p.sections._categories.fields.categories.list = rsp.categories;
            p.sections._subscriptions.fields.subscriptions.list = rsp.subscriptions;
            p.refresh();
            p.show(cb);
        });
    }
    this.printcatalog.downloadPDF = function() {    
        var args = {'business_id':M.curBusinessID, 'output':'download'};
        args['categories'] = this.formFieldValue(this.formField('categories'), 'categories');
        M.api.openPDF('ciniki.foodmarket.productCatalogPDF', args);
    }
    this.printcatalog.emailTestPDF = function() {
        var c = this.serializeForm();
        M.api.postJSONCb('ciniki.foodmarket.productCatalogPDF', {'business_id':M.curBusinessID, 'output':'testemail'}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            alert("Email send, please check your inbox.");
        });
    }
    this.printcatalog.emailPDF = function() {
        var c = this.serializeForm();
        M.api.postJSONCb('ciniki.foodmarket.productCatalogPDF', {'business_id':M.curBusinessID, 'output':'mailinglists'}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            alert("Email sent.");
        });
    }
    this.printcatalog.addClose('Cancel');

    //
    // The panel to edit Note
    //
    this.note = new M.panel('Note', 'ciniki_foodmarket_main', 'note', 'mc', 'medium', 'sectioned', 'ciniki.foodmarket.main.note');
    this.note.data = null;
    this.note.note_id = 0;
    this.note.nplist = [];
    this.note.sections = {
        'general':{'label':'', 'fields':{
            'note_date':{'label':'Date', 'required':'yes', 'type':'date'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '60':'Archived'}},
            'customer_id':{'label':'Customer', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            }},
        '_content':{'label':'Content', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_foodmarket_main.note.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_foodmarket_main.note.note_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_foodmarket_main.note.remove();'},
            }},
        };
    this.note.fieldValue = function(s, i, d) { return this.data[i]; }
    this.note.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.poma.noteHistory', 'args':{'business_id':M.curBusinessID, 'note_id':this.note_id, 'field':i}};
    }
    this.note.open = function(cb, nid, cid, list) {
        if( nid != null ) { this.note_id = nid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.poma.noteGet', {'business_id':M.curBusinessID, 'note_id':this.note_id, 'customer_id':cid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_foodmarket_main.note;
            p.data = rsp.note;
            p.sections.general.fields.customer_id.options = rsp.customers;
            p.sections.general.fields.customer_id.options.unshift({'id':0, 'name':'No Customer'});
            p.refresh();
            p.show(cb);
        });
    }
    this.note.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_foodmarket_main.note.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.note_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.poma.noteUpdate', {'business_id':M.curBusinessID, 'note_id':this.note_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.poma.noteAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.note.note_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.note.remove = function() {
        if( confirm('Are you sure you want to remove note?') ) {
            M.api.getJSONCb('ciniki.poma.noteDelete', {'business_id':M.curBusinessID, 'note_id':this.note_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_foodmarket_main.note.close();
            });
        }
    }
    this.note.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.note_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_foodmarket_main.note.save(\'M.ciniki_foodmarket_main.note.open(null,' + this.nplist[this.nplist.indexOf('' + this.note_id) + 1] + ');\');';
        }
        return null;
    }
    this.note.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.note_id) > 0 ) {
            return 'M.ciniki_foodmarket_main.note.save(\'M.ciniki_foodmarket_main.note_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.note_id) - 1] + ');\');';
        }
        return null;
    }
    this.note.addButton('save', 'Save', 'M.ciniki_foodmarket_main.note.save();');
    this.note.addClose('Cancel');
    this.note.addButton('next', 'Next');
    this.note.addLeftButton('prev', 'Prev');

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
