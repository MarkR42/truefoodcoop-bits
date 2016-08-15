import java.util.Date;
import java.text.SimpleDateFormat;
import javax.swing.JOptionPane;

System.out.println("ADD LINE1");
// Debug: dump line properties.
line.getProperties().list(System.out);
// check if the item is a TFC voucher.
if (line.getProperty("tfc.voucher") != null) {
	System.out.println("TFC VOUCHER ACTIVATED");
	// Delete previous voucher, if it exists.
	for (int i = (ticket.getLinesCount() -1); i>0; i--) {
		otherline = ticket.getLine(i);
		if (otherline.getProperty("tfc.voucher") != null) {
			ticket.removeLine(i);
		}
	}
	// Get current time, check not expired.
	fmt = new SimpleDateFormat("yyyy-MM-dd");
	String datestr = fmt.format(new Date());
	System.out.println("CURRENT DATE:" + datestr);
	String dateexp = line.getProperty("tfc.expires");
	if (dateexp != null) {
		if (datestr.compareTo(dateexp) >= 0) {
			// Voucher expired!
			System.out.println(" VOUCHER EXPIRED");
			JOptionPane.showMessageDialog(null, "Voucher expired:" + dateexp +
				". Sorry.",
				"Voucher not accepted.", 
				JOptionPane.PLAIN_MESSAGE);
			return 1;
		}
	}

	// Discount rate, percent.
	discount = Double.parseDouble(line.getProperty("tfc.discount"));
	System.out.println("TFC DISCOUNT RATE:" + discount);
	// Apply discount (in percent, negative)
        discountAmount = (discount * -0.01) * ticket.getTotal();
	// Give the customer the benefit - round to whole pennies.
	discountPence = Math.floor(discountAmount * 100.0);
	line.setPrice(discountPence * 0.01);
}
