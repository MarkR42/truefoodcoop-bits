import java.util.Date;
import java.text.SimpleDateFormat;
import javax.swing.JOptionPane;

System.out.println("EVENT.CHANGE1");

if (ticket.customer != null) {
/* Interpret customer.notes as a list of months with discounts, e.g.

2020-02:10.0
2020-03:5.0
*/
    Float discount = 0.0; // percent.
    fmt = new SimpleDateFormat("yyyy-MM");
    String monthstr = fmt.format(new Date());
    System.out.println("CURRENT month:" + monthstr);

    System.out.println("ticket.customer.name=" + ticket.customer.name);
    // System.out.println("ticket.customer.notes=" + ticket.customer.notes);
    bits = ticket.customer.notes.split("\n");
    for (int i=0; i< bits.length; i++) {
        System.out.println(bits[i]);
        colonpos = bits[i].indexOf(':');
        if ((bits[i].startsWith(monthstr)) && (colonpos > 0)) {
            discountstr = bits[i].substring(colonpos + 1);
            System.out.println("discountstr=" + discountstr);
            discount = Float.parseFloat(discountstr);
        }
    }
    if (discount > 0.0) {
        System.out.println("Customer should get discount percentage: " + discount);
        // Delete any previous voucher, if it exists.
        for (int i = (ticket.getLinesCount() -1); i>=0; i--) {
            otherline = ticket.getLine(i);
            if (otherline.getProperty("tfc.voucher") != null) {
                ticket.removeLine(i);
            }
        }

        // Add discount line
        // Set this to the product ID of the members specific discount voucher.
        // Must be set correctly for back-end reporting.
        PRODUCT_ID_DISCOUNT = "bcb482e8-b32e-43a8-aad4-c4ef7d6fc9d6";

        discountAmount = (discount * -0.01) * ticket.getTotal();
        discountPence = Math.round(discountAmount * 100.0);
        discountRounded = discountPence * 0.01;

        System.out.println("discountAmount=" + discountAmount.toString());
        discountLine = new com.openbravo.pos.ticket.TicketLineInfo(
            PRODUCT_ID_DISCOUNT, 1, discountRounded, null);
        discountLine.setProperty("tfc.voucher", "customer");
        discountLine.setProperty("tfc.discountPercent", discount.toString());
        discountLine.setProperty("product.name", "ActiveDisc @" + discount.toString() + "%");
        discountLine.setProperty("product.taxcategoryid", "000");// no tax
        // Do not add a zero-value discount.
        if (discountAmount < -0.01) {
            ticket.addLine(discountLine);
        }
    }
}

