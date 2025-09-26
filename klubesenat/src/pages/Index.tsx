import { AppHeader } from "@/components/AppHeader";
import { AppFooter } from "@/components/AppFooter";
import { BalanceCard } from "@/components/BalanceCard";
import { TransactionHistory } from "@/components/TransactionHistory";

const mockTransactions = [
  {
    id: "686",
    date: "26/09/2025",
    establishment: "Sync Holding",
    time: "15:58",
    purchaseAmount: 1200.00,
    amountPaid: 1200.00,
    balanceUsed: 0,
    cashbackAmount: 120.00,
    cashbackPercentage: 10.0,
    status: "confirmado" as const,
  },
  {
    id: "687",
    date: "24/09/2025", 
    establishment: "Farmácia Drogasil",
    time: "10:15",
    purchaseAmount: 125.00,
    amountPaid: 100.00,
    balanceUsed: 25.00,
    cashbackAmount: 25.00,
    cashbackPercentage: 20.0,
    status: "confirmado" as const,
  },
  {
    id: "688",
    date: "22/09/2025",
    establishment: "Clínica São José",
    time: "14:30", 
    purchaseAmount: 200.00,
    amountPaid: 150.00,
    balanceUsed: 50.00,
    cashbackAmount: 40.00,
    cashbackPercentage: 20.0,
    status: "confirmado" as const,
  },
];

const Index = () => {
  return (
    <div className="min-h-screen bg-background">
      <AppHeader />
      
      <div className="max-w-md mx-auto -mt-4 space-y-6 pb-8">
        <BalanceCard balance={150.00} />
        <TransactionHistory transactions={mockTransactions} />
      </div>

      <AppFooter />
    </div>
  );
};

export default Index;
