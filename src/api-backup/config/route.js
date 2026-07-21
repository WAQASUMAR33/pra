import { NextResponse } from 'next/server';
import prisma from '@/lib/prisma';

export async function GET() {
  try {
    let config = await prisma.merchantConfig.findFirst({
      where: { isActive: true }
    });
    
    if (!config) {
      // Return default values if none exist
      config = {
        posId: "820816",
        token: "2D79A61F",
        branchName: "Lahore Main Branch",
        branchAddress: "Gulberg III, Lahore, Punjab",
        apiUrl: "https://ims.pral.com.pk/ims/sandbox/api/Live/PostData",
        isActive: true
      };
    }
    
    return NextResponse.json(config);
  } catch (error) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function POST(request) {
  try {
    const data = await request.json();
    const { posId, token, branchName, branchAddress, apiUrl } = data;

    if (!posId || !token || !branchName || !branchAddress) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    // Set all other configs to inactive
    await prisma.merchantConfig.updateMany({
      data: { isActive: false }
    });

    // Create new active config
    const config = await prisma.merchantConfig.create({
      data: {
        posId,
        token,
        branchName,
        branchAddress,
        apiUrl: apiUrl || "https://ims.pral.com.pk/ims/sandbox/api/Live/PostData",
        isActive: true
      }
    });

    return NextResponse.json({ success: true, config });
  } catch (error) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
