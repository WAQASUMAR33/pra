import { NextResponse } from 'next/server';
import prisma from '@/lib/prisma';

export async function GET(request) {
  try {
    const { searchParams } = new URL(request.url);
    const limit = parseInt(searchParams.get('limit') || '50');
    
    const invoices = await prisma.invoice.findMany({
      take: limit,
      orderBy: {
        createdAt: 'desc'
      },
      include: {
        items: true
      }
    });
    
    return NextResponse.json(invoices);
  } catch (error) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function POST(request) {
  try {
    const data = await request.json();
    const {
      buyerNtn,
      buyerCnic,
      buyerName,
      buyerPhone,
      invoiceType = 1,
      paymentMode = 1,
      items = [],
      totalDiscount = 0,
      eventType,
      eventDate,
      numberOfGuests
    } = data;

    if (!items || items.length === 0) {
      return NextResponse.json({ error: "Invoice must have at least one item." }, { status: 400 });
    }

    // Get active config to read POSID
    const config = await prisma.merchantConfig.findFirst({
      where: { isActive: true }
    });
    const posId = config ? config.posId : "820816";

    // Auto-generate invoice number and USIN
    const timestamp = Date.now();
    const rand = Math.floor(1000 + Math.random() * 9000);
    const usin = `${posId}-${timestamp}-${rand}`;
    const invoiceNumber = `INV-${timestamp.toString().slice(-6)}-${rand.toString().slice(-3)}`;

    // Calculate totals server-side
    let totalQuantity = 0;
    let totalSaleValue = 0; // Gross
    let totalTaxCharged = 0;
    let computedItems = [];

    for (const item of items) {
      const qty = parseInt(item.quantity) || 0;
      const rate = parseFloat(item.taxRate) || 0;
      const price = parseFloat(item.price) || 0; // Unit price
      const discount = parseFloat(item.discount || 0);

      const saleValue = qty * price;
      const taxAmount = (saleValue - discount) * (rate / 100);
      const netAmount = saleValue - discount + taxAmount;

      totalQuantity += qty;
      totalSaleValue += saleValue;
      totalTaxCharged += taxAmount;

      computedItems.push({
        itemCode: item.itemCode || `ITM-${Math.floor(100 + Math.random() * 900)}`,
        itemName: item.itemName,
        quantity: qty,
        pctCode: item.pctCode || "00000000",
        taxRate: rate,
        saleValue,
        salesTaxApplicable: taxAmount,
        discount,
        netAmount,
        furtherTax: 0,
        federalTax: 0
      });
    }

    const totalBillAmount = totalSaleValue - parseFloat(totalDiscount) + totalTaxCharged;

    // Save in Database
    const invoice = await prisma.invoice.create({
      data: {
        invoiceNumber,
        posId,
        usin,
        buyerNtn,
        buyerCnic,
        buyerName,
        buyerPhone,
        invoiceType,
        totalQuantity,
        totalSaleValue,
        totalTaxCharged,
        totalDiscount: parseFloat(totalDiscount),
        totalBillAmount,
        paymentMode,
        eventType,
        eventDate: eventDate ? new Date(eventDate) : null,
        numberOfGuests: numberOfGuests ? parseInt(numberOfGuests) : null,
        status: 'DRAFT', // Default to draft
        items: {
          create: computedItems
        }
      },
      include: {
        items: true
      }
    });

    return NextResponse.json({ success: true, invoice });
  } catch (error) {
    console.error("API Create Invoice Error:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
