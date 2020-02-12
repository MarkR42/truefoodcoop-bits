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
        discountLine = new com.openbravo.pos.ticket.TicketLineInfo();
        discountLine.setProperty("tfc.voucher", "customer");
        discountLine.setProperty("product.name", "ActiveMember Discount");
        discountLine.setProperty("product.taxcategoryid", "000");// no tax
        discountLine.setMultiply(1.0);
        discountAmount = (discount * -0.01) * ticket.getTotal();
        System.out.println("discountAmount=" + discountAmount.toString());
        discountPence = Math.round(discountAmount * 100.0);
        discountLine.setPrice(discountPence * 0.01);
        // Do not add a zero-value discount.
        if (discountAmount < -0.01) {
            ticket.addLine(discountLine);
        }
    }
}

