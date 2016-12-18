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

====
SOYA MILKS 12/12/2016

select id, reference, code, pricesell, name from products where name like '%soya%' and reference like '%inf%' and (code like '%24_' or code like '%15_') order by code;
+--------------------------------------+----------------+---------------+-----------+-------------------------------------+
| id                                   | reference      | code          | pricesell | name                                |
+--------------------------------------+----------------+---------------+-----------+-------------------------------------+
| 1a24bde4-ea6d-46a0-afa3-59cfe5286850 | 724805;;INF    | 3273227080156 |      1.54 | Soya milk - green - unsweetened 1L  |
| 8f05eec3-01b1-4af9-894b-6ba6bf860c05 | old724610;;INF | 3273227080248 |      1.45 | Soya milk with Calcium 1litre       |
| aeb9aaca-52a7-4f09-af4d-bb2efabde42a | 724605;;INF    | 3273229480152 |      8.05 | Soya - green - 6x1ltr               |
| 487de863-c796-4b4e-9b25-b6c9c2a00c6e | 724610;;INF    | 3273229480244 |      9.35 | Soya (blue) 6x1ltr                  |
| XX ignore the soya beans
+--------------------------------------+----------------+---------------+-----------+-------------------------------------+
5 rows in set (0.01 sec)


