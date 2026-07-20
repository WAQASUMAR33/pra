import prisma from './prisma';

/**
 * Formats local DB invoice data into the official PRA/PRAL JSON payload format.
 * @param {object} invoice - Invoice object loaded from Prisma (with items relation)
 * @param {object} config - MerchantConfig object
 */
export function formatPRAInvoicePayload(invoice, config) {
  return {
    InvoiceNumber: invoice.invoiceNumber || "",
    POSID: parseInt(config.posId) || config.posId, // FBR/PRA API often expects integer POSID
    USIN: invoice.usin,
    DateTime: formatDateTimeForPRA(invoice.dateTime),
    BuyerNTN: invoice.buyerNtn || "",
    BuyerCNIC: invoice.buyerCnic || "",
    BuyerName: invoice.buyerName || "",
    BuyerPhoneNumber: invoice.buyerPhone || "",
    InvoiceType: invoice.invoiceType || 1, // 1 = Sale, 2 = Credit Note (Return), 3 = Debit Note
    TotalQuantity: invoice.totalQuantity,
    TotalSaleValue: parseFloat(invoice.totalSaleValue),
    TotalTaxCharged: parseFloat(invoice.totalTaxCharged),
    TotalDiscount: parseFloat(invoice.totalDiscount || 0),
    TotalBillAmount: parseFloat(invoice.totalBillAmount),
    PaymentMode: invoice.paymentMode || 1, // 1 = Cash, 2 = Card, 3 = Wallet
    Items: invoice.items.map(item => ({
      ItemCode: item.itemCode,
      ItemName: item.itemName,
      Quantity: item.quantity,
      PCTCode: item.pctCode || "00000000", // Standard default PCT Code if not provided
      TaxRate: parseFloat(item.taxRate),
      SaleValue: parseFloat(item.saleValue),
      SalesTaxApplicable: parseFloat(item.salesTaxApplicable),
      FurtherTax: parseFloat(item.furtherTax || 0),
      FederalTax: parseFloat(item.federalTax || 0),
      Discount: parseFloat(item.discount || 0),
      InvoiceType: invoice.invoiceType || 1
    }))
  };
}

/**
 * Format Date to YYYY-MM-DD HH:mm:ss for PRA/FBR Integration.
 */
function formatDateTimeForPRA(dateInput) {
  const d = new Date(dateInput);
  const pad = (n) => String(n).padStart(2, '0');
  
  const yyyy = d.getFullYear();
  const MM = pad(d.getMonth() + 1);
  const dd = pad(d.getDate());
  const HH = pad(d.getHours());
  const mm = pad(d.getMinutes());
  const ss = pad(d.getSeconds());
  
  return `${yyyy}-${MM}-${dd} ${HH}:${mm}:${ss}`;
}

/**
 * Sends formatted invoice to PRA API.
 * Falls back to dry-run mock response if token is 'sandbox' or is invalid.
 */
export async function uploadInvoiceToPRA(invoiceId) {
  try {
    // 1. Fetch invoice with items
    const invoice = await prisma.invoice.findUnique({
      where: { id: invoiceId },
      include: { items: true }
    });

    if (!invoice) {
      throw new Error(`Invoice with ID ${invoiceId} not found.`);
    }

    // 2. Fetch active configuration
    const config = await prisma.merchantConfig.findFirst({
      where: { isActive: true }
    });

    if (!config) {
      throw new Error("No active PRA POS configuration found. Please configure the settings first.");
    }

    // 3. Mark invoice as PENDING
    await prisma.invoice.update({
      where: { id: invoiceId },
      data: { status: 'PENDING' }
    });

    // 4. Format payload
    const payload = formatPRAInvoicePayload(invoice, config);

    // 5. Check if it's in demo/sandbox simulated mode
    const isSimulated = !config.token || config.token.toLowerCase() === 'sandbox' || config.token.toLowerCase() === 'mock' || config.token.includes('demo');

    let responseData;
    let requestTime = new Date();

    if (isSimulated) {
      // Mock successful response from PRA
      // Wait for 800ms to simulate network latency
      await new Promise(resolve => setTimeout(resolve, 800));

      // Generate a mock fiscal invoice number based on FBR/PRA pattern:
      // PRA-POSID-YYYYMMDD-USIN
      const yyyymmdd = new Date().toISOString().slice(0, 10).replace(/-/g, '');
      const mockFiscalNo = `PRA-${config.posId}-${yyyymmdd}-${invoice.usin.slice(-6)}`;

      responseData = {
        ResponseCode: "00",
        ResponseMsg: "SUCCESS (Simulated Sandbox Response)",
        InvoiceNumber: mockFiscalNo,
        USIN: invoice.usin,
        QRCode: `https://e.pra.punjab.gov.pk/verify?fiscalNumber=${mockFiscalNo}&usin=${invoice.usin}&posid=${config.posId}`
      };
    } else {
      // Real API request
      const response = await fetch(config.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${config.token}`
        },
        body: JSON.stringify(payload)
      });

      if (!response.ok) {
        throw new Error(`PRA Endpoint returned HTTP status: ${response.status}`);
      }

      responseData = await response.json();
    }

    // 6. Update invoice with submission status and response details
    // Standard response codes: "00" indicates success.
    const isSuccess = responseData.ResponseCode === "00";
    const status = isSuccess ? 'SUCCESS' : 'FAILED';

    const updatedInvoice = await prisma.invoice.update({
      where: { id: invoiceId },
      data: {
        status,
        praFiscalNumber: responseData.InvoiceNumber || null,
        praResponseCode: responseData.ResponseCode || null,
        praResponseMsg: responseData.ResponseMsg || JSON.stringify(responseData),
      }
    });

    return {
      success: isSuccess,
      invoice: updatedInvoice,
      payload,
      response: responseData
    };

  } catch (error) {
    console.error("PRA Submission Error:", error);
    
    // Mark invoice as failed in DB
    try {
      await prisma.invoice.update({
        where: { id: invoiceId },
        data: {
          status: 'FAILED',
          praResponseMsg: error.message || "An unknown error occurred during upload."
        }
      });
    } catch (dbErr) {
      console.error("Failed to update status to FAILED in DB:", dbErr);
    }

    return {
      success: false,
      error: error.message
    };
  }
}
