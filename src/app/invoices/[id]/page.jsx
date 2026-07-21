import InvoiceDetailsClient from './InvoiceDetailsClient';

export async function generateStaticParams() {
  // Return a placeholder list of IDs for static export
  return [
    { id: 'placeholder' }
  ];
}

export default function Page({ params }) {
  const { id } = params;
  return <InvoiceDetailsClient id={id} />;
}
