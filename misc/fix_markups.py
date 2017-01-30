#!/usr/bin/env python3

# This removes some of the attributes from the "attributes" field
# in the products table.
#
# These affect the behaviour of the Infinity reprice script.
# They aren't used by the EPOS.

import mysql.connector
import os
import sys
import re

def connect_to_db(host, username, password, db):
    conn = mysql.connector.connect(user=username, password=password,
                              host=host,
                              database=db)
                              
    return conn
    
def init_z_categories(conn):
    conn.autocommit = True
    cur = conn.cursor()
    cur.execute("DROP TABLE IF EXISTS zcategories")
    cur.execute("create table zcategories as select id from categories where name like 'z-%'")

def main():
    try:
        conn = connect_to_db(*sys.argv[1:5])
    except TypeError:
        print("Required arguments: host, username, password, db")
        raise
    print("CONNECT OK")
    init_z_categories(conn)
    # Supply this flag -c to commit changes.
    do_commit = '-c' in sys.argv
    
    cur = conn.cursor()
    cur.execute("SELECT id, reference, attributes, name FROM products "
        " WHERE reference like '%;;INF' and attributes IS NOT NULL "
        " AND category NOT IN (select id from zcategories) "
        " ORDER BY reference"
        )
    # The correct markup value.
    oldf = open('old.txt', 'w', encoding="utf-8")
    newf = open('new.txt', 'w', encoding="utf-8")
    new_attributes_by_product = {}
    for prod_id, prod_reference, prod_attributes, prod_name in cur:
        attributes = str(prod_attributes, 'ascii')
        
        # Remove "priceMatch" attribute, which keeps the price the same
        # forever (unless manually changed, but then changes it back next time)
        attributes2 = re.sub(r'priceMatch=[0-9.]+;+', '', attributes)
        # Remove our nominalMarkup value - which means the reprice script
        # will need to reset it to the default (correct) value next time.
        attributes2 = re.sub(r'nominalMarkup=[0-9.]+;+', '', attributes2)
        if attributes2 != attributes:
            info = "reference={}, name={}".format( prod_reference, prod_name)
            print(info, file=oldf)
            print(info, file=newf)
            print(attributes.replace(';;','\n'), file=oldf)
            print(attributes2.replace(';;','\n'), file=newf)
            new_attributes_by_product[prod_id] = attributes2
    oldf.close()
    newf.close()
    print("OK")
    if do_commit:
        conn.autocommit = False
        cur = conn.cursor()
        for prod_id, new_attributes in new_attributes_by_product.items():
            cur.execute("UPDATE products SET attributes=%s WHERE id=%s",
                (new_attributes, prod_id))
        conn.commit()
        print("DB CHANGES DONE!")
    else:
        print("Not writing changes. To make changes, add -c option")
        
    
    
if __name__ == '__main__':
    main()
