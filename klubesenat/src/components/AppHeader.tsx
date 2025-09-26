import { Settings, User } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useNavigate } from "react-router-dom";
import sestSenatLogo from "@/assets/sest-senat-logo.jpg";

interface AppHeaderProps {
  showBackButton?: boolean;
  title?: string;
}

export function AppHeader({ showBackButton = false, title }: AppHeaderProps) {
  const navigate = useNavigate();

  return (
    <header className="bg-gradient-primary p-4 pb-8">
      {/* Logo SEST SENAT centralizada no topo */}
      <div className="flex justify-center mb-4">
        <img 
          src={sestSenatLogo} 
          alt="SEST SENAT - CNT | SEST SENAT | ITL Sistema Transporte" 
          className="h-16 object-contain"
        />
      </div>
      
      {/* Header com navegação */}
      <div className="flex items-center justify-between max-w-md mx-auto">
        <div className="flex items-center space-x-2">
          {showBackButton && (
            <Button 
              variant="ghost" 
              size="sm" 
              onClick={() => navigate(-1)}
              className="text-primary-foreground hover:bg-primary-light/20 p-2"
            >
              ←
            </Button>
          )}
          {/* Espaço reservado para logo Klube Cash */}
          <div className="text-2xl font-bold text-primary-foreground opacity-0">
            Klube Cash
          </div>
        </div>
        
        {!showBackButton && (
          <Button 
            variant="ghost" 
            size="sm"
            onClick={() => navigate('/profile')}
            className="text-primary-foreground hover:bg-primary-light/20 p-2"
          >
            <Settings className="h-6 w-6" />
          </Button>
        )}
      </div>
    </header>
  );
}