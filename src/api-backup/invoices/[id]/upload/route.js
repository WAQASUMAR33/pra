import { NextResponse } from 'next/server';
import { uploadInvoiceToPRA } from '@/lib/praService';

export async function POST(request, { params }) {
  try {
    const { id } = params;
    const result = await uploadInvoiceToPRA(id);
    
    if (!result.success) {
      return NextResponse.json({
        success: false,
        error: result.error || "PRA submission failed",
        response: result.response
      }, { status: 400 });
    }

    return NextResponse.json({
      success: true,
      invoice: result.invoice,
      payload: result.payload,
      response: result.response
    });
  } catch (error) {
    console.error("Upload API route handler error:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
