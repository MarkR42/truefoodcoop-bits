Catalogue maintenance utilities.

These are PHP scripts which have a menu. 

We run them on the reporting server. 

===

STOCK CONTROL

1. We have a utility to set the stock to zero, either for individual
    product or the whole shop.
    
    When we do a whole shop stock-take, we set the stocks all to zero.
    
1. Stock import loader

    This takes the supplier's spreadsheet and loads it into the DB.
    
    - Tries to figure out the correct columns.
    - Tries to determine the box quantity
    - For loose prouducts (bag of product, weight in kg), then
        the stocking quantity should be weight in kg.
    
1. Special stocks.

    a. Things which are sold in a different box quantity
        - 5kg Oats bag. It is not sold by kg, but the whole bag.
        - This should OVERRIDE the box quantity.
        
    b. Things which are sold as a "box" of something else:
        - Eggs "Half dozen"
        - Eggs "Whole tray"
        - 6x Soya milk (2 products)
        
        Those products are not stocked, their stock is taken from
        another product.
        
        N units of the other products are "joined together".
        
        tfc_special_stock_rules table is used:
        


CREATE TABLE tfc_special_stock_rules (
    REFERENCE varchar(255) NOT NULL PRIMARY KEY, -- products.reference key
    override_box_quantity FLOAT, -- If null, normal box quantity is used.
    take_stock_from_reference varchar(255), -- "Consume" stock from this product
    take_stock_quantity INTEGER -- "Consume" this many units of stock from the other product.
);
