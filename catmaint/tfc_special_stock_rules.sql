CREATE TABLE tfc_special_stock_rules (
    REFERENCE varchar(255) NOT NULL PRIMARY KEY, -- products.reference key
    override_box_quantity FLOAT, -- If null, normal box quantity is used.
    take_stock_from_reference varchar(255), -- "Consume" stock from this product
    take_stock_quantity INTEGER -- "Consume" this many units of stock from the other product.
);

/*
| 051ecedf-c954-4806-93d6-4a63314e4064 | 20530;;INF | 4.6 Cereals & Flakes | Fine porridge oats 5kg |
| 10e4744e-129f-4628-ba4c-f48b546fd726 | 62040;;inf | 4 Loose Foods        | Muesli Base 5kg        |
| 8d80df04-ff25-4d45-b497-736d26624429 | 20520;;INF | 4 Loose Foods        | Jumbo oats 5kg         |
| b8a5db35-d5ad-4c63-b3e2-f1977740923a | 62030;;INF | 4.6 Cereals & Flakes | Super Muesli 5kg       |
*/

-- Items which we sell in 5kg bags.

INSERT INTO tfc_special_stock_rules (reference, override_box_quantity)
VALUES
    ('20530;;INF', 1),
    ('62040;;inf', 1),
    ('20520;;INF', 1),
    ('62030;;INF', 1);
    
